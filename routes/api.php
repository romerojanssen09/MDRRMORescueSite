<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

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
        'data' => $specializations
    ]);
});

// Health check
Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});
