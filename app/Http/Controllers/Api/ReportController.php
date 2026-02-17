<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmergencyReport;
use App\Models\User;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    protected $pushNotificationService;

    public function __construct(PushNotificationService $pushNotificationService)
    {
        $this->pushNotificationService = $pushNotificationService;
    }

    /**
     * Mark report as completed (rescuer only)
     */
    public function markAsCompleted(Request $request, $reportId)
    {
        $validated = $request->validate([
            'rescuer_id' => 'required|exists:users,id',
        ]);

        $report = EmergencyReport::findOrFail($reportId);

        // Verify user is a rescuer
        $rescuer = User::findOrFail($validated['rescuer_id']);
        if ($rescuer->role !== 'rescuer') {
            return response()->json([
                'success' => false,
                'message' => 'Only rescuers can mark reports as completed',
            ], 403);
        }

        // Verify the rescuer is assigned to this report (individually or via team)
        $isAssigned = $report->assigned_rescuer_id === $validated['rescuer_id'] ||
                     ($report->assigned_team_id && $rescuer->rescue_team_id === $report->assigned_team_id);

        if (!$isAssigned) {
            return response()->json([
                'success' => false,
                'message' => 'You are not assigned to this report',
            ], 403);
        }

        // Update report status
        $report->update(['status' => 'Completed']);

        // If report was assigned to a team, update team status and notify team members
        if ($report->assigned_team_id) {
            $team = \App\Models\RescueTeam::find($report->assigned_team_id);
            if ($team) {
                $team->update(['status' => 'Available']);
                \Log::info("Team {$team->name} status updated to Available after completing report {$report->id}");
                
                // Notify other team members that mission is completed
                $this->notifyTeamMissionCompleted($report, $team, $rescuer);
            }
        }

        // Send notification to citizen
        $this->pushNotificationService->notifyReportCompleted($report, $rescuer);

        return response()->json([
            'success' => true,
            'message' => 'Report marked as completed. Citizen has been notified.' . 
                        ($report->assigned_team_id ? ' Team status updated to Available.' : ''),
            'report' => $report,
        ]);
    }

    /**
     * Notify team members when mission is completed
     */
    private function notifyTeamMissionCompleted($report, $team, $completedBy)
    {
        try {
            // Get all team members except the one who completed it
            $teamMembers = User::where('rescue_team_id', $team->id)
                ->where('id', '!=', $completedBy->id)
                ->get();

            if ($teamMembers->isEmpty()) {
                return;
            }

            \Log::info("Notifying {$teamMembers->count()} team member(s) of mission completion");

            // Get push tokens for team members
            $tokens = \DB::table('push_tokens')
                ->whereIn('user_id', $teamMembers->pluck('id'))
                ->where('is_active', true)
                ->get();

            if ($tokens->isEmpty()) {
                \Log::info('No active push tokens for team members');
                return;
            }

            // Prepare notifications
            $title = '✅ Team Mission Completed';
            $body = "{$completedBy->full_name} completed the {$report->emergency_type} mission. Team is now Available.";

            $notifications = $tokens->map(function($token) use ($title, $body, $report) {
                return [
                    'to' => $token->push_token,
                    'sound' => 'default',
                    'title' => $title,
                    'body' => $body,
                    'data' => [
                        'reportId' => $report->id,
                        'type' => 'mission_completed',
                        'emergencyType' => $report->emergency_type,
                    ],
                    'priority' => 'default',
                    'channelId' => 'emergency-reports',
                ];
            })->toArray();

            // Send via Expo Push Notification service
            $response = \Http::post('https://exp.host/--/api/v2/push/send', $notifications);

            \Log::info('Team completion notifications sent', ['response' => $response->json()]);
        } catch (\Exception $e) {
            \Log::error('Error notifying team of mission completion: ' . $e->getMessage());
        }
    }

    /**
     * Get rescuer's assigned reports
     */
    public function getRescuerReports(Request $request)
    {
        $rescuerId = $request->input('rescuer_id');

        if (!$rescuerId) {
            return response()->json([
                'success' => false,
                'message' => 'rescuer_id is required',
            ], 400);
        }

        $reports = EmergencyReport::where('assigned_rescuer_id', $rescuerId)
            ->with('citizen')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'reports' => $reports,
        ]);
    }

    /**
     * Update expo push token
     */
    public function updateExpoPushToken(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'expo_push_token' => 'required|string',
        ]);

        $user = User::find($validated['user_id']);
        $user->update(['expo_push_token' => $validated['expo_push_token']]);

        return response()->json([
            'success' => true,
            'message' => 'Expo push token updated successfully',
        ]);
    }
}
