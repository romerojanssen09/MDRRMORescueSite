<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmergencyReport;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // Cache stats for 30 seconds to reduce database load
        $stats = Cache::remember('dashboard_stats', 30, function () {
            // Use a single query to get all report counts by status
            $reportCounts = DB::table('emergency_reports')
                ->selectRaw("
                    COUNT(*) as total,
                    COUNT(CASE WHEN status = 'Pending' THEN 1 END) as pending,
                    COUNT(CASE WHEN status = 'In Progress' THEN 1 END) as in_progress,
                    COUNT(CASE WHEN status = 'Completed' THEN 1 END) as completed
                ")
                ->first();

            // Use a single query to get all user counts by role
            $userCounts = DB::table('users')
                ->selectRaw("
                    COUNT(*) as total,
                    COUNT(CASE WHEN role = 'citizen' THEN 1 END) as citizens,
                    COUNT(CASE WHEN role = 'rescuer' THEN 1 END) as rescuers
                ")
                ->first();

            // Use a single query to get team counts
            $teamCounts = DB::table('rescue_teams')
                ->selectRaw("
                    COUNT(*) as total,
                    COUNT(CASE WHEN status = 'Available' THEN 1 END) as available
                ")
                ->first();

            // Get available rescuers count
            $availableRescuers = DB::table('rescuer_profiles')
                ->where('status', 'available')
                ->count();

            return [
                'total_reports' => $reportCounts->total,
                'pending_reports' => $reportCounts->pending,
                'in_progress_reports' => $reportCounts->in_progress,
                'completed_reports' => $reportCounts->completed,
                'total_users' => $userCounts->total,
                'total_citizens' => $userCounts->citizens,
                'total_rescuers' => $userCounts->rescuers,
                'available_rescuers' => $availableRescuers,
                'total_teams' => $teamCounts->total,
                'available_teams' => $teamCounts->available,
            ];
        });

        // Cache recent reports for 10 seconds
        $recent_reports = Cache::remember('dashboard_recent_reports', 10, function () {
            return EmergencyReport::with(['citizen:id,full_name', 'assignedRescuer:id,full_name'])
                ->select('id', 'emergency_type', 'location', 'status', 'citizen_id', 'assigned_rescuer_id', 'created_at')
                ->latest()
                ->limit(10)
                ->get();
        });

        // Cache emergency types for 60 seconds
        $emergency_types = Cache::remember('dashboard_emergency_types', 60, function () {
            return DB::table('emergency_reports')
                ->select('emergency_type', DB::raw('count(*) as count'))
                ->groupBy('emergency_type')
                ->orderByDesc('count')
                ->get();
        });

        return view('admin.dashboard', compact('stats', 'recent_reports', 'emergency_types'));
    }
}
