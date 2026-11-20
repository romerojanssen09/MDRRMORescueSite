<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    private const BUCKET_MAX_KB = [
        'emergency-images' => 5120,
        'avatars' => 2048,
    ];

    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    /**
     * Store an uploaded image in the configured Wasmer/local uploads volume.
     */
    public function store(Request $request, string $bucket)
    {
        if (! isset(self::BUCKET_MAX_KB[$bucket])) {
            abort(404);
        }

        $maxKb = self::BUCKET_MAX_KB[$bucket];

        $request->validate([
            'file' => "required|file|mimes:jpeg,jpg,png,webp|max:{$maxKb}",
            'filename' => ['nullable', 'string', 'max:120', 'regex:/^[A-Za-z0-9_-]+(?:\.(?:jpg|jpeg|png|webp))?$/i'],
        ]);

        $file = $request->file('file');
        $ext = strtolower($file->extension() ?: 'png');
        if ($ext === 'jpeg') {
            $ext = 'jpg';
        }

        $id = $request->filled('filename')
            ? pathinfo((string) $request->input('filename'), PATHINFO_FILENAME)
            : (string) Str::uuid();

        $filename = "{$id}.{$ext}";
        $storedPath = Storage::disk('uploads')->putFileAs($bucket, $file, $filename);

        $relativeUrl = "/uploads/{$bucket}/{$id}";
        $publicUrl = (app()->environment('production') && config('app.url') && in_array($request->getHost(), ['localhost', '127.0.0.1', '0.0.0.0'], true))
            ? rtrim((string) config('app.url'), '/').$relativeUrl
            : url($relativeUrl);

        return response()->json([
            'success' => true,
            'bucket' => $bucket,
            'id' => $id,
            'filename' => $filename,
            'path' => $storedPath,
            'url' => $publicUrl,
        ], 201);
    }

    /**
     * Stream an uploaded file from the uploads volume.
     */
    public function show(string $bucket, string $path)
    {
        $storedPath = $this->resolveStoredPath($bucket, $path);
        if ($storedPath === null) {
            abort(404);
        }

        return Storage::disk('uploads')->response($storedPath, null, [
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }

    /**
     * Delete an uploaded file from the uploads volume.
     */
    public function destroy(string $bucket, string $path)
    {
        $storedPath = $this->resolveStoredPath($bucket, $path);
        if ($storedPath === null) {
            abort(404);
        }

        Storage::disk('uploads')->delete($storedPath);

        return response()->json([
            'success' => true,
            'deleted' => $storedPath,
        ]);
    }

    private function resolveStoredPath(string $bucket, string $item): ?string
    {
        if (! isset(self::BUCKET_MAX_KB[$bucket])) {
            return null;
        }

        if (! preg_match('/^[A-Za-z0-9_-]+(?:\.[A-Za-z0-9]+)?$/', $item)) {
            return null;
        }

        $disk = Storage::disk('uploads');

        if (str_contains($item, '.')) {
            $candidate = "{$bucket}/{$item}";

            return $disk->exists($candidate) ? $candidate : null;
        }

        foreach (self::ALLOWED_EXTENSIONS as $ext) {
            $candidate = "{$bucket}/{$item}.{$ext}";
            if ($disk->exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
