<?php

use App\Http\Controllers\Api\MobileAuthController;
use App\Http\Controllers\Api\MobileLocationController;
use App\Http\Controllers\Api\MobileMessageController;
use App\Http\Controllers\Api\MobileNotificationController;
use App\Http\Controllers\Api\MobileReportController;
use App\Http\Controllers\Api\UploadController;
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
    try {
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
    } catch (\Throwable $e) {
        $url = (string) (config('database.connections.pgsql.url') ?: '');
        $configuredHost = ($url !== '' ? parse_url($url, PHP_URL_HOST) : null) ?: config('database.connections.pgsql.host');
        $sanitizedMessage = preg_replace('#://[^@\s]+@#', '://***:***@', $e->getMessage()) ?? $e->getMessage();

        return response()->json([
            'status' => 'error',
            'default_connection' => config('database.default'),
            'pdo_drivers' => \PDO::getAvailableDrivers(),
            'has_database_url' => ! empty(env('DATABASE_URL')),
            'has_db_url' => ! empty(env('DB_URL')),
            'configured_host' => $configuredHost,
            'error_class' => get_class($e),
            'error' => $sanitizedMessage,
        ], 503);
    }
});

// Persistent uploads volume health check
Route::get('/health/storage', function () {
    $root = (string) config('filesystems.disks.uploads.root');
    $exists = is_dir($root);
    $writable = $exists ? is_writable($root) : is_writable(dirname($root));

    return response()->json([
        'status' => $writable ? 'ok' : 'error',
        'default_disk' => config('filesystems.default'),
        'uploads_root' => $root,
        'dir_exists' => $exists,
        'writable' => $writable,
    ], $writable ? 200 : 503);
});

// Wasmer persistent volume uploads (emergency-images, avatars)
Route::post('/uploads/{bucket}', [UploadController::class, 'store'])
    ->whereIn('bucket', ['emergency-images', 'avatars']);
Route::delete('/uploads/{bucket}/{path}', [UploadController::class, 'destroy'])
    ->whereIn('bucket', ['emergency-images', 'avatars'])
    ->where('path', '.*');

// Mobile App API (Neon PostgreSQL + PostGIS backend)
Route::prefix('mobile')->group(function () {
    // Auth & Profile & OTP
    Route::post('/auth/register', [MobileAuthController::class, 'register']);
    Route::post('/auth/login', [MobileAuthController::class, 'login']);
    Route::get('/auth/me', [MobileAuthController::class, 'me']);
    Route::patch('/auth/profile', [MobileAuthController::class, 'updateProfile']);
    Route::post('/auth/change-password', [MobileAuthController::class, 'changePassword']);
    Route::post('/auth/otp/send', [MobileAuthController::class, 'sendOtp']);
    Route::post('/auth/otp/verify', [MobileAuthController::class, 'verifyOtp']);
    Route::post('/auth/reset-password', [MobileAuthController::class, 'resetPassword']);

    // Emergency Reports & Dispatch
    Route::get('/reports', [MobileReportController::class, 'index']);
    Route::get('/reports/daily-limit', [MobileReportController::class, 'dailyLimit']);
    Route::get('/reports/{id}', [MobileReportController::class, 'show']);
    Route::post('/reports', [MobileReportController::class, 'store']);
    Route::post('/reports/{id}/take-action', [MobileReportController::class, 'takeAction']);
    Route::post('/reports/{id}/complete', [MobileReportController::class, 'complete']);

    // Live Rescuer GPS Locations
    Route::post('/locations', [MobileLocationController::class, 'store']);
    Route::get('/locations/report/{reportId}', [MobileLocationController::class, 'forReport']);
    Route::post('/locations/stop', [MobileLocationController::class, 'stop']);

    // Report Chat & Unread Counts
    Route::get('/messages/conversations', [MobileMessageController::class, 'conversations']);
    Route::get('/messages/unread-summary', [MobileMessageController::class, 'unreadSummary']);
    Route::get('/messages/{reportId}', [MobileMessageController::class, 'index']);
    Route::post('/messages/{reportId}', [MobileMessageController::class, 'store']);
    Route::post('/messages/{reportId}/read', [MobileMessageController::class, 'markRead']);

    // Push Tokens & Notifications
    Route::post('/push-tokens', [MobileNotificationController::class, 'savePushToken']);
    Route::delete('/push-tokens', [MobileNotificationController::class, 'deletePushToken']);
    Route::get('/notifications', [MobileNotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [MobileNotificationController::class, 'markRead']);
    Route::post('/notifications/read-all', [MobileNotificationController::class, 'markAllRead']);
    Route::delete('/notifications/{id}', [MobileNotificationController::class, 'destroy']);
});
