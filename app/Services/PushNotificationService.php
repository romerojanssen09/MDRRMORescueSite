<?php

namespace App\Services;

use App\Models\UserNotification;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class PushNotificationService
{
    /**
     * Send Expo push notification directly to a token
     */
    public function sendNotification($expoPushToken, $title, $body, $data = [])
    {
        return $this->sendExpoPushNotification($expoPushToken, $title, $body, $data);
    }

    /**
     * Send Expo push notification
     */
    private function sendExpoPushNotification($expoPushToken, $title, $body, $data = [])
    {
        if (!$expoPushToken || !str_starts_with($expoPushToken, 'ExponentPushToken[')) {
            Log::warning('Invalid Expo push token', ['token' => $expoPushToken]);
            return false;
        }

        try {
            $response = Http::post('https://exp.host/--/api/v2/push/send', [
                'to' => $expoPushToken,
                'title' => $title,
                'body' => $body,
                'sound' => 'default',
                'priority' => 'high',
                'data' => $data,
            ]);

            if ($response->successful()) {
                Log::info('Expo push notification sent', [
                    'token' => $expoPushToken,
                    'title' => $title,
                ]);
                return true;
            } else {
                Log::error('Expo push notification failed', [
                    'token' => $expoPushToken,
                    'response' => $response->body(),
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Expo push notification exception', [
                'token' => $expoPushToken,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send notification to a user
     */
    public function sendToUser($userId, $title, $body, $type = 'info', $relatedReportId = null, $additionalData = [])
    {
        try {
            // Create notification in database
            $notification = UserNotification::create([
                'user_id' => $userId,
                'title' => $title,
                'body' => $body,
                'type' => $type,
                'related_report_id' => $relatedReportId,
                'data' => $additionalData,
            ]);

            Log::info('Notification created', [
                'notification_id' => $notification->id,
                'user_id' => $userId,
                'title' => $title,
            ]);

            // Send Expo push notification using push_tokens table
            $pushTokens = \DB::table('push_tokens')
                ->where('user_id', $userId)
                ->where('is_active', true)
                ->get();
            
            foreach ($pushTokens as $tokenRecord) {
                $this->sendExpoPushNotification(
                    $tokenRecord->push_token,
                    $title,
                    $body,
                    array_merge($additionalData, [
                        'notification_id' => $notification->id,
                        'related_report_id' => $relatedReportId,
                    ])
                );
            }

            // The mobile app will also receive this via Supabase Realtime subscription
            
            return $notification;
        } catch (\Exception $e) {
            Log::error('Failed to send notification', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Send notification when rescue team is assigned
     */
    public function notifyTeamAssigned($report, $team)
    {
        // Notify citizen
        if ($report->citizen_id) {
            $this->sendToUser(
                $report->citizen_id,
                'Rescue Team Assigned',
                "Rescue team '{$team->team_name}' has been assigned to your emergency report. Help is on the way!",
                'success',
                $report->id,
                [
                    'team_name' => $team->team_name,
                    'emergency_type' => $report->emergency_type,
                ]
            );
        }

        // Notify all team members using push_tokens table
        $teamMembers = User::where('rescue_team_id', $team->id)
            ->where('role', 'rescuer')
            ->get();

        Log::info('Notifying team members', [
            'team_id' => $team->id,
            'team_name' => $team->team_name,
            'members_count' => $teamMembers->count(),
            'report_id' => $report->id,
        ]);

        foreach ($teamMembers as $member) {
            // Get active push tokens from push_tokens table
            $pushTokens = \DB::table('push_tokens')
                ->where('user_id', $member->id)
                ->where('is_active', true)
                ->get();
            
            Log::info('Sending push notification to team member', [
                'user_id' => $member->id,
                'user_name' => $member->full_name,
                'tokens_count' => $pushTokens->count(),
            ]);
            
            foreach ($pushTokens as $tokenRecord) {
                $this->sendNotification(
                    $tokenRecord->push_token,
                    'New Team Assignment',
                    "Your team has been assigned to a {$report->emergency_type} emergency at {$report->location}",
                    [
                        'type' => 'team_assignment',
                        'reportId' => $report->id,
                        'report_id' => $report->id, // Add both formats for compatibility
                        'team_name' => $team->team_name,
                        'emergency_type' => $report->emergency_type,
                        'location' => $report->location,
                    ]
                );
            }
            
            // Also create user notification in database
            UserNotification::create([
                'user_id' => $member->id,
                'title' => 'New Team Assignment',
                'body' => "Your team has been assigned to a {$report->emergency_type} emergency at {$report->location}",
                'type' => 'team_assignment',
                'related_report_id' => $report->id,
                'data' => [
                    'team_name' => $team->team_name,
                    'emergency_type' => $report->emergency_type,
                    'location' => $report->location,
                ],
            ]);
        }

        Log::info('Team assignment notifications sent', [
            'team_id' => $team->id,
            'team_name' => $team->team_name,
            'members_notified' => $teamMembers->count(),
        ]);
    }

    /**
     * Send notification when rescuer is assigned
     */
    public function notifyRescuerAssigned($report, $rescuer)
    {
        // Notify citizen
        if ($report->citizen_id) {
            $this->sendToUser(
                $report->citizen_id,
                'Rescuer Assigned',
                "A rescuer ({$rescuer->full_name}) has been assigned to your emergency report. Help is on the way!",
                'success',
                $report->id,
                [
                    'rescuer_name' => $rescuer->full_name,
                    'rescuer_phone' => $rescuer->phone,
                    'emergency_type' => $report->emergency_type,
                ]
            );
        }

        // Notify rescuer
        $this->sendToUser(
            $rescuer->id,
            'New Assignment',
            "You have been assigned to a {$report->emergency_type} emergency at {$report->location}",
            'warning',
            $report->id,
            [
                'emergency_type' => $report->emergency_type,
                'location' => $report->location,
                'citizen_name' => $report->citizen->full_name ?? 'Unknown',
                'citizen_phone' => $report->citizen->phone ?? null,
            ]
        );
    }

    /**
     * Send notification when report is completed
     */
    public function notifyReportCompleted($report, $rescuer)
    {
        // Notify citizen
        if ($report->citizen_id) {
            $this->sendToUser(
                $report->citizen_id,
                'Report Resolved',
                "Your {$report->emergency_type} emergency report has been resolved by {$rescuer->full_name}. Stay safe!",
                'success',
                $report->id,
                [
                    'rescuer_name' => $rescuer->full_name,
                    'emergency_type' => $report->emergency_type,
                    'completed_at' => now()->toDateTimeString(),
                ]
            );
        }
    }
}
