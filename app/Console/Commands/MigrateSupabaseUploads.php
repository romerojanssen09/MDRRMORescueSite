<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class MigrateSupabaseUploads extends Command
{
    private const SOURCE_PATTERN = '@/storage/v1/object/public/(emergency-images|avatars)/([^/?#]+)$@i';

    private const ALLOWED_FILENAME_PATTERN = '/^[A-Za-z0-9_-]+\.(?:jpg|jpeg|png|webp)$/i';

    protected $signature = 'uploads:migrate-from-supabase
        {--target-url= : Base URL of the deployed Wasmer app (defaults to APP_URL, e.g. https://mdrrmo-rescue-site.wasmer.app)}
        {--dry-run : Preview matched Supabase URLs and target Wasmer URLs without uploading or updating the database}
        {--local : Write directly to the local configured uploads disk instead of uploading via HTTP to --target-url}';

    protected $description = 'Migrate existing Supabase Storage bucket images to Wasmer /data/uploads and update database URLs';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $targetUrl = rtrim((string) ($this->option('target-url') ?: config('app.url') ?: 'https://mdrrmo-rescue-site.wasmer.app'), '/');
        $dryRun = (bool) $this->option('dry-run');
        $local = (bool) $this->option('local');

        $records = $this->collectSupabaseRecords($targetUrl);

        if ($records === []) {
            $this->info('No Supabase Storage public URLs found in emergency_reports or users.');

            return self::SUCCESS;
        }

        $this->info(sprintf('Matched %d record(s) with Supabase Storage URLs (target: %s).', count($records), $targetUrl));

        if ($dryRun) {
            $this->table(
                ['Location', 'Row ID', 'Bucket', 'Filename', 'Target URL'],
                array_map(fn (array $item) => [
                    "{$item['table']}.{$item['column']}",
                    $item['id'],
                    $item['bucket'],
                    $item['filename'],
                    $item['expected_url'],
                ], $records)
            );
            $this->comment('Dry-run complete. Zero files were transferred and zero database rows were modified.');

            return self::SUCCESS;
        }

        if (! $local) {
            $healthUrl = "{$targetUrl}/api/health";
            $this->line("Probing target health endpoint: {$healthUrl}");

            try {
                $health = Http::timeout(15)->acceptJson()->get($healthUrl);
            } catch (\Throwable $e) {
                $this->error("Wasmer target {$targetUrl} is not reachable ({$e->getMessage()}). No database rows were modified.");

                return self::FAILURE;
            }

            if ($health->status() !== 200) {
                $this->error("Wasmer target {$targetUrl} returned HTTP {$health->status()} on /api/health. Deploy Wasmer first before migrating URLs. No database rows were modified.");

                return self::FAILURE;
            }
        }

        $uploadedCount = 0;
        $skippedCount = 0;
        $updatedCount = 0;
        $failedCount = 0;
        $transferredFiles = [];

        foreach ($records as $item) {
            $cacheKey = "{$item['bucket']}/{$item['filename']}";

            try {
                if (isset($transferredFiles[$cacheKey])) {
                    $finalUrl = $transferredFiles[$cacheKey];
                } elseif ($local) {
                    $finalUrl = $this->transferToLocalDisk($item, $uploadedCount, $skippedCount);
                    $transferredFiles[$cacheKey] = $finalUrl;
                } else {
                    $finalUrl = $this->transferToRemoteWasmer($item, $targetUrl, $uploadedCount, $skippedCount);
                    $transferredFiles[$cacheKey] = $finalUrl;
                }

                DB::table($item['table'])
                    ->where('id', $item['id'])
                    ->update([$item['column'] => $finalUrl]);

                $updatedCount++;
                $this->line("OK [{$item['table']}.{$item['column']}] {$item['id']} -> {$finalUrl}");
            } catch (\Throwable $e) {
                $failedCount++;
                $this->error("FAIL [{$item['table']}.{$item['column']}] {$item['id']} ({$item['filename']}): {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info(sprintf(
            'Migration summary: matched=%d, uploaded=%d, already_present=%d, db_updated=%d, failed=%d',
            count($records),
            $uploadedCount,
            $skippedCount,
            $updatedCount,
            $failedCount
        ));

        return $failedCount > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Collect all matching Supabase Storage URLs from emergency_reports and users.
     *
     * @return array<int, array<string, string>>
     */
    private function collectSupabaseRecords(string $targetUrl): array
    {
        $targets = [
            ['table' => 'emergency_reports', 'column' => 'image_url'],
            ['table' => 'emergency_reports', 'column' => 'photo_url'],
            ['table' => 'users', 'column' => 'avatar_url'],
        ];

        $records = [];

        foreach ($targets as $target) {
            $table = $target['table'];
            $column = $target['column'];

            $rows = DB::table($table)
                ->select(['id', $column])
                ->where($column, 'like', '%/storage/v1/object/public/%')
                ->orderBy('id')
                ->get();

            foreach ($rows as $row) {
                $sourceUrl = (string) $row->{$column};
                if (! preg_match(self::SOURCE_PATTERN, $sourceUrl, $matches)) {
                    $this->warn("Skipping unrecognized URL format in {$table}.{$column} ({$row->id}): {$sourceUrl}");
                    continue;
                }

                $bucket = strtolower($matches[1]);
                $filename = urldecode($matches[2]);

                if (! preg_match(self::ALLOWED_FILENAME_PATTERN, $filename)) {
                    $this->warn("Skipping unsafe filename in {$table}.{$column} ({$row->id}): {$filename}");
                    continue;
                }

                $stem = pathinfo($filename, PATHINFO_FILENAME);

                $records[] = [
                    'table' => $table,
                    'column' => $column,
                    'id' => (string) $row->id,
                    'bucket' => $bucket,
                    'filename' => $filename,
                    'stem' => $stem,
                    'source_url' => $sourceUrl,
                    'expected_url' => "{$targetUrl}/uploads/{$bucket}/{$stem}",
                ];
            }
        }

        return $records;
    }

    private function transferToLocalDisk(array $item, int &$uploadedCount, int &$skippedCount): string
    {
        $disk = Storage::disk('uploads');
        $storedPath = "{$item['bucket']}/{$item['filename']}";

        if ($disk->exists($storedPath) && $disk->size($storedPath) > 0) {
            $skippedCount++;

            return $item['expected_url'];
        }

        $bytes = $this->downloadSourceBytes($item['source_url']);
        if (! $disk->put($storedPath, $bytes)) {
            throw new \RuntimeException("Failed writing {$storedPath} to local uploads disk.");
        }

        if (! $disk->exists($storedPath) || $disk->size($storedPath) <= 0) {
            throw new \RuntimeException("Verification failed on local uploads disk for {$storedPath}.");
        }

        $uploadedCount++;

        return $item['expected_url'];
    }

    private function transferToRemoteWasmer(array $item, string $targetUrl, int &$uploadedCount, int &$skippedCount): string
    {
        $expectedUrl = $item['expected_url'];

        $existingCheck = Http::timeout(15)->get($expectedUrl);
        if ($existingCheck->status() === 200 && strlen($existingCheck->body()) > 0 && str_starts_with(strtolower((string) $existingCheck->header('Content-Type')), 'image/')) {
            $skippedCount++;

            return $expectedUrl;
        }

        $bytes = $this->downloadSourceBytes($item['source_url']);
        $uploadEndpoint = "{$targetUrl}/api/uploads/{$item['bucket']}";

        $uploadResponse = Http::timeout(60)
            ->acceptJson()
            ->attach('file', $bytes, $item['filename'])
            ->post($uploadEndpoint, [
                'filename' => $item['filename'],
            ]);

        if ($uploadResponse->status() !== 201) {
            throw new \RuntimeException("Upload HTTP {$uploadResponse->status()}: ".substr($uploadResponse->body(), 0, 300));
        }

        $payload = $uploadResponse->json();
        $finalUrl = (is_array($payload) && ! empty($payload['url'])) ? (string) $payload['url'] : $expectedUrl;

        $verify = Http::timeout(20)->get($finalUrl);
        if ($verify->status() !== 200 || strlen($verify->body()) === 0) {
            throw new \RuntimeException("Live verification failed for {$finalUrl} (HTTP {$verify->status()}).");
        }

        $uploadedCount++;

        return $finalUrl;
    }

    private function downloadSourceBytes(string $sourceUrl): string
    {
        $response = Http::timeout(30)->get($sourceUrl);

        if (! $response->successful() || strlen($response->body()) === 0) {
            $serviceKey = config('services.supabase.service_key');
            if ($serviceKey) {
                $response = Http::timeout(30)->withHeaders([
                    'apikey' => $serviceKey,
                    'Authorization' => 'Bearer '.$serviceKey,
                ])->get($sourceUrl);
            }
        }

        if (! $response->successful() || strlen($response->body()) === 0) {
            throw new \RuntimeException("Source download failed (HTTP {$response->status()}) for {$sourceUrl}");
        }

        return $response->body();
    }
}
