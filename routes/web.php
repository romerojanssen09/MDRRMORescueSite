<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EmergencyReportController;
use App\Http\Controllers\Admin\RescueTeamController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Auth Routes
Route::get('/admin/login', [LoginController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin/login', [LoginController::class, 'login'])->name('admin.login.submit');
Route::post('/admin/logout', [LoginController::class, 'logout'])->name('admin.logout');

// Admin Routes
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    Route::resource('users', UserController::class);
    Route::get('/users-sync', [UserController::class, 'syncFromAuth'])->name('users.sync');
    Route::resource('reports', EmergencyReportController::class)->except(['create', 'store', 'edit']);
    Route::get('/reports/{report}/assign-map', [EmergencyReportController::class, 'assignMap'])->name('reports.assign-map');
    Route::resource('teams', RescueTeamController::class);
    Route::post('/teams/{team}/reassign-members', [RescueTeamController::class, 'reassignMembers'])->name('teams.reassign-members');
});
