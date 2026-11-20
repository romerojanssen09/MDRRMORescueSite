<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmergencyReport;
use App\Models\RescueTeam;
use App\Models\User;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MobileReportController extends Controller
{
    private const DAILY_REPORT_LIMIT = 3;

    public function __construct(private readonly PushNotificationService $pushService)
    {
    }

    /**
     * List emergency reports with optional filters for mobile screens.
     */
    public function index(Request $request)
    {
        $query = EmergencyReport::with([
            'citizen:id,full_name,phone,email',
            'assignedRescuer:id,full_name,phone,email',
            'assignedTeam:id,team_name,specialization,status',
        ]);

        if ($request->filled('citizen_id')) {
            $query->where('citizen_id', $request->input('citizen_id'));
        }

        if ($request->filled('device_id')) {
            $query->where('device_id', $request->input('device_id'));
        }

        if ($request->filled('assigned_rescuer_id')) {
            $query->where('assigned_rescuer_id', $request->input('assigned_rescuer_id'));
        }

        if ($request->filled('assigned_team_id')) {
            $query->where('assigned_team_id', $request->input('assigned_team_id'));
        }

        if ($request->filled('status')) {
            $statuses = array_filter(array_map('trim', explode(',', (string) $request->input('status'))));
            if (count($statuses) === 1) {
                $query->where('status', $statuses[0]);
            } elseif (count($statuses) > 1) {
                $query->whereIn('status', $statuses);
            }
        }

        if ($request->filled('emergency_type')) {
            $query->where('emergency_type', $request->input('emergency_type'));
        }

        $limit = min(max((int) $request->input('limit', 100), 1), 200);
        $reports = $query->orderByDesc('created_at')->limit($limit)->get();

        return response()->json([
            'success' => true,
            'reports' => $reports,
        ]);
    }

    /**
     * Fetch a single emergency report with relations.
     */
    public function show(string $id)
    {
        $report = EmergencyReport::with([
            'citizen:id,full_name,phone,email',
            'assignedRescuer:id,full_name,phone,email',
            'assignedTeam:id,team_name,specialization,status',
        ])->find($id);

        if (! $report) {
            return response()->json(['success' => false, 'error' => 'Report not found'], 404);
        }

        return response()->json([
            'success' => true,
            'report' => $report,
        ]);
    }

    /**
     * Check daily emergency report limit (3 per day per device).
     */
    public function dailyLimit(Request $request)
    {
        $deviceId = (string) $request->input('device_id', '');
        $citizenId = $request->input('citizen_id');

        if ($deviceId === '' && empty($citizenId)) {
            return response()->json([
                'canReport' => true,
                'reportsToday' => 0,
                'limit' => self::DAILY_REPORT_LIMIT,
                'remainingReports' => self::DAILY_REPORT_LIMIT,
            ]);
        }

        $startOfDay = now()->startOfDay();
        $endOfDay = now()->endOfDay();

        $query = DB::table('emergency_reports')
            ->whereBetween('created_at', [$startOfDay, $endOfDay]);

        if (! empty($citizenId)) {
            $query->where('citizen_id', $citizenId);
            if ($deviceId !== '') {
                $query->where('device_id', $deviceId);
            }
        } else {
            $query->where('device_id', $deviceId);
        }

        $reportsToday = (int) $query->count();
        $remaining = max(0, self::DAILY_REPORT_LIMIT - $reportsToday);

        return response()->json([
            'canReport' => $reportsToday < self::DAILY_REPORT_LIMIT,
            'reportsToday' => $reportsToday,
            'limit' => self::DAILY_REPORT_LIMIT,
            'remainingReports' => $remaining,
        ]);
    }

    /**
     * Create a new emergency report (citizen or guest) and notify active rescuers.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'citizen_id' => 'nullable|uuid|exists:users,id',
            'device_id' => 'nullable|string|max:255',
            'emergency_type' => 'required|string|max:255',
            'location' => 'required|string|max:1000',
            'description' => 'nullable|string|max:5000',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'image_url' => 'nullable|string|max:2048',
            'photo_url' => 'nullable|string|max:2048',
            'show_name' => 'nullable|boolean',
            'guest_name' => 'nullable|string|max:255',
            'guest_contact' => 'nullable|string|max:50',
            'is_guest_report' => 'nullable|boolean',
        ]);

        $reportId = (string) Str::uuid();
        $isGuest = empty($validated['citizen_id']);

        DB::table('emergency_reports')->insert([
            'id' => $reportId,
            'citizen_id' => $validated['citizen_id'] ?? null,
            'device_id' => $validated['device_id'] ?? null,
            'emergency_type' => $validated['emergency_type'],
            'location' => $validated['location'],
            'description' => $validated['description'] ?? '',
            'status' => 'Pending',
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'image_url' => $validated['image_url'] ?? $validated['photo_url'] ?? null,
            'photo_url' => $validated['photo_url'] ?? $validated['image_url'] ?? null,
            'show_name' => $validated['show_name'] ?? true,
            'guest_name' => $isGuest ? ($validated['guest_name'] ?? null) : null,
            'guest_contact' => $isGuest ? ($validated['guest_contact'] ?? null) : null,
            'is_guest_report' => $validated['is_guest_report'] ?? $isGuest,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $report = EmergencyReport::with([
            'citizen:id,full_name,phone,email',
        ])->findOrFail($reportId);

        // Notify verified rescuers (best effort)
        $rescuers = User::where('role', 'rescuer')
            ->where('is_active', true)
            ->where('is_verified', true)
            ->get();

        foreach ($rescuers as $rescuer) {
            $this->pushService->sendToUser(
                $rescuer->id,
                "🚨 New {$report->emergency_type} Report",
                "Location: {$report->location}",
                'new_report',
                $report->id,
                ['reportId' => $report->id, 'emergencyType' => $report->emergency_type]
            );
        }

        return response()->json([
            'success' => true,
            'report' => $report,
        ], 201);
    }

    /**
     * Rescuer accepts a pending report (individual or team mode).
     */
    public function takeAction(Request $request, string $id)
    {
        $validated = $request->validate([
            'rescuer_id' => 'required|uuid|exists:users,id',
            'mode' => 'nullable|in:individual,team',
        ]);

        $report = EmergencyReport::findOrFail($id);
        if ($report->status !== 'Pending') {
            return response()->json([
                'success' => false,
                'error' => 'This report has already been accepted or completed.',
            ], 409);
        }

        $rescuer = User::findOrFail($validated['rescuer_id']);
        $mode = $validated['mode'] ?? ($rescuer->rescue_team_id ? 'team' : 'individual');

        DB::transaction(function () use ($report, $rescuer, $mode) {
            if ($mode === 'team' && $rescuer->rescue_team_id) {
                $report->update([
                    'assigned_team_id' => $rescuer->rescue_team_id,
                    'assigned_rescuer_id' => $rescuer->id,
                    'status' => 'In Progress',
                ]);

                RescueTeam::where('id', $rescuer->rescue_team_id)->update([
                    'status' => 'On Mission',
                    'updated_at' => now(),
                ]);

                DB::table('rescue_operations')->insert([
                    'id' => (string) Str::uuid(),
                    'emergency_report_id' => $report->id,
                    'rescue_team_id' => $rescuer->rescue_team_id,
                    'status' => 'Assigned',
                    'started_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                User::where('rescue_team_id', $rescuer->rescue_team_id)
                    ->pluck('id')
                    ->each(function ($memberId) {
                        DB::table('rescuer_profiles')
                            ->where('user_id', $memberId)
                            ->update(['status' => 'on_duty', 'updated_at' => now()]);
                    });
            } else {
                $report->update([
                    'assigned_rescuer_id' => $rescuer->id,
                    'status' => 'In Progress',
                ]);

                DB::table('rescuer_profiles')
                    ->where('user_id', $rescuer->id)
                    ->update(['status' => 'on_duty', 'updated_at' => now()]);
            }
        });

        $freshReport = EmergencyReport::with([
            'citizen:id,full_name,phone,email',
            'assignedRescuer:id,full_name,phone,email',
            'assignedTeam:id,team_name,specialization,status',
        ])->findOrFail($report->id);

        if ($freshReport->citizen_id) {
            $responderName = $freshReport->assignedTeam?->team_name ?: $rescuer->full_name;
            $this->pushService->sendToUser(
                $freshReport->citizen_id,
                '🚑 Rescuer On The Way',
                "{$responderName} is responding to your {$freshReport->emergency_type} report.",
                'rescuer_taking_action',
                $freshReport->id,
                ['reportId' => $freshReport->id]
            );
        }

        return response()->json([
            'success' => true,
            'report' => $freshReport,
        ]);
    }

    /**
     * Mark an In Progress report as Completed and restore team/rescuer availability.
     */
    public function complete(Request $request, string $id)
    {
        $validated = $request->validate([
            'rescuer_id' => 'required|uuid|exists:users,id',
        ]);

        $report = EmergencyReport::findOrFail($id);
        $rescuer = User::findOrFail($validated['rescuer_id']);

        $isAssigned = $report->assigned_rescuer_id === $rescuer->id
            || ($report->assigned_team_id && $rescuer->rescue_team_id === $report->assigned_team_id);

        if (! $isAssigned && $rescuer->role !== 'admin') {
            return response()->json([
                'success' => false,
                'error' => 'You are not assigned to this report.',
            ], 403);
        }

        DB::transaction(function () use ($report, $rescuer) {
            $report->update(['status' => 'Completed']);

            if ($report->assigned_team_id) {
                RescueTeam::where('id', $report->assigned_team_id)->update([
                    'status' => 'Available',
                    'updated_at' => now(),
                ]);

                DB::table('rescue_operations')
                    ->where('emergency_report_id', $report->id)
                    ->update([
                        'status' => 'Completed',
                        'completed_at' => now(),
                        'updated_at' => now(),
                    ]);

                User::where('rescue_team_id', $report->assigned_team_id)
                    ->pluck('id')
                    ->each(function ($memberId) {
                        DB::table('rescuer_profiles')
                            ->where('user_id', $memberId)
                            ->update(['status' => 'available', 'updated_at' => now()]);
                    });
            } else {
                DB::table('rescuer_profiles')
                    ->where('user_id', $rescuer->id)
                    ->update(['status' => 'available', 'updated_at' => now()]);
            }

            DB::table('rescuer_locations')
                ->where('report_id', $report->id)
                ->update(['is_active' => false, 'updated_at' => now()]);
        });

        $this->pushService->notifyReportCompleted($report, $rescuer);

        $freshReport = EmergencyReport::with([
            'citizen:id,full_name,phone,email',
            'assignedRescuer:id,full_name,phone,email',
            'assignedTeam:id,team_name,specialization,status',
        ])->findOrFail($report->id);

        return response()->json([
            'success' => true,
            'report' => $freshReport,
        ]);
    }
}
