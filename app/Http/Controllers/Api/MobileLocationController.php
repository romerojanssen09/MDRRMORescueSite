<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MobileLocationController extends Controller
{
    /**
     * Upsert live rescuer GPS coordinates for an active report.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'rescuer_id' => 'required|uuid|exists:users,id',
            'report_id' => 'required|uuid|exists:emergency_reports,id',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric',
            'heading' => 'nullable|numeric',
            'speed' => 'nullable|numeric',
        ]);

        $existing = DB::table('rescuer_locations')
            ->where('rescuer_id', $validated['rescuer_id'])
            ->where('report_id', $validated['report_id'])
            ->first();

        if ($existing) {
            DB::table('rescuer_locations')
                ->where('id', $existing->id)
                ->update([
                    'latitude' => $validated['latitude'],
                    'longitude' => $validated['longitude'],
                    'accuracy' => $validated['accuracy'] ?? null,
                    'heading' => $validated['heading'] ?? null,
                    'speed' => $validated['speed'] ?? null,
                    'is_active' => true,
                    'updated_at' => now(),
                ]);
        } else {
            DB::table('rescuer_locations')->insert([
                'id' => (string) Str::uuid(),
                'rescuer_id' => $validated['rescuer_id'],
                'report_id' => $validated['report_id'],
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'accuracy' => $validated['accuracy'] ?? null,
                'heading' => $validated['heading'] ?? null,
                'speed' => $validated['speed'] ?? null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Fetch the active rescuer location for a given emergency report.
     */
    public function forReport(string $reportId)
    {
        $location = DB::table('rescuer_locations')
            ->where('report_id', $reportId)
            ->where('is_active', true)
            ->orderByDesc('updated_at')
            ->first();

        return response()->json([
            'success' => true,
            'location' => $location,
        ]);
    }

    /**
     * Mark rescuer location tracking inactive.
     */
    public function stop(Request $request)
    {
        $validated = $request->validate([
            'rescuer_id' => 'required|uuid|exists:users,id',
            'report_id' => 'nullable|uuid',
        ]);

        $query = DB::table('rescuer_locations')
            ->where('rescuer_id', $validated['rescuer_id'])
            ->where('is_active', true);

        if (! empty($validated['report_id'])) {
            $query->where('report_id', $validated['report_id']);
        }

        $query->update([
            'is_active' => false,
            'updated_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }
}
