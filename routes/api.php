<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public API routes (no authentication required)
Route::get('/specializations', function () {
    $specializations = DB::table('specializations')
        ->where('is_active', true)
        ->orderBy('name')
        ->select('id', 'name', 'description')
        ->get();

    return response()->json([
        'success' => true,
        'data' => $specializations,
    ]);
});

// PostGIS location mapping: find rescue teams within radius_km of (lat, lng)
Route::get('/teams/nearest', function (Request $request) {
    $validated = $request->validate([
        'lat' => 'required|numeric|between:-90,90',
        'lng' => 'required|numeric|between:-180,180',
        'radius_km' => 'nullable|numeric|min:0.1|max:500',
    ]);

    $lat = (float) $validated['lat'];
    $lng = (float) $validated['lng'];
    $radiusMeters = ((float) ($validated['radius_km'] ?? 50)) * 1000;

    $teams = DB::table('rescue_teams')
        ->select([
            'id',
            'team_name',
            'specialization',
            'status',
            'province',
            'municipality',
            'barangay',
            'latitude',
            'longitude',
            'members_count',
        ])
        ->selectRaw(
            'ROUND((ST_Distance(location_point, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography) / 1000.0)::numeric, 2) AS distance_km',
            [$lng, $lat]
        )
        ->whereNotNull('location_point')
        ->whereRaw(
            'ST_DWithin(location_point, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, ?)',
            [$lng, $lat, $radiusMeters]
        )
        ->orderByRaw("CASE WHEN status = 'Available' THEN 0 ELSE 1 END")
        ->orderBy('distance_km')
        ->get();

    return response()->json([
        'success' => true,
        'data' => $teams,
    ]);
});

// Fast lightweight health check (used by InstaBoot)
Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

// Live Neon PostgreSQL + PostGIS connectivity check
Route::get('/health/db', function () {
    $pgVersion = DB::selectOne('SELECT version() AS version')->version ?? null;
    $postgisVersion = DB::selectOne('SELECT PostGIS_Version() AS version')->version ?? null;

    return response()->json([
        'status' => 'ok',
        'database' => DB::connection()->getDatabaseName(),
        'postgres_version' => $pgVersion,
        'postgis_version' => $postgisVersion,
        'tables' => [
            'specializations' => DB::table('specializations')->count(),
            'rescue_teams' => DB::table('rescue_teams')->count(),
            'users' => DB::table('users')->count(),
            'emergency_reports' => DB::table('emergency_reports')->count(),
        ],
    ]);
});
