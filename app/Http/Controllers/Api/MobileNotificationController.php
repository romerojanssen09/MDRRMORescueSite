<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MobileNotificationController extends Controller
{
    /**
     * Save or refresh an Expo push token for a mobile user.
     */
    public function savePushToken(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|uuid|exists:users,id',
            'push_token' => 'required|string|max:512',
            'device_type' => 'nullable|string|max:50',
        ]);

        $now = now();
        $existing = DB::table('push_tokens')
            ->where('push_token', $validated['push_token'])
            ->first();

        if ($existing) {
            DB::table('push_tokens')
                ->where('id', $existing->id)
                ->update([
                    'user_id' => $validated['user_id'],
                    'device_type' => $validated['device_type'] ?? $existing->device_type ?? 'android',
                    'is_active' => true,
                    'updated_at' => $now,
                ]);
        } else {
            DB::table('push_tokens')->insert([
                'id' => (string) Str::uuid(),
                'user_id' => $validated['user_id'],
                'push_token' => $validated['push_token'],
                'device_type' => $validated['device_type'] ?? 'android',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('users')
            ->where('id', $validated['user_id'])
            ->update([
                'expo_push_token' => $validated['push_token'],
                'updated_at' => $now,
            ]);

        return response()->json(['success' => true]);
    }

    /**
     * Deactivate push tokens on sign-out.
     */
    public function deletePushToken(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|uuid|exists:users,id',
            'push_token' => 'nullable|string|max:512',
        ]);

        $query = DB::table('push_tokens')->where('user_id', $validated['user_id']);
        if (! empty($validated['push_token'])) {
            $query->where('push_token', $validated['push_token']);
        }
        $query->update(['is_active' => false, 'updated_at' => now()]);

        DB::table('users')->where('id', $validated['user_id'])->update([
            'expo_push_token' => null,
            'updated_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * List user_notifications for a user.
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|uuid|exists:users,id',
        ]);

        $notifications = DB::table('user_notifications')
            ->where('user_id', $validated['user_id'])
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(function ($row) {
                $row->is_read = (bool) ($row->is_read || $row->read);
                $row->read = $row->is_read;
                if (is_string($row->data)) {
                    $row->data = json_decode($row->data, true) ?: [];
                }

                return $row;
            });

        $unreadCount = $notifications->where('is_read', false)->count();

        return response()->json([
            'success' => true,
            'notifications' => $notifications->values(),
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Mark a single notification as read.
     */
    public function markRead(string $id)
    {
        $updated = DB::table('user_notifications')
            ->where('id', $id)
            ->update([
                'is_read' => true,
                'read' => true,
                'read_at' => now(),
                'updated_at' => now(),
            ]);

        if (! $updated) {
            return response()->json(['success' => false, 'error' => 'Notification not found'], 404);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Mark all notifications for user_id as read.
     */
    public function markAllRead(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|uuid|exists:users,id',
        ]);

        DB::table('user_notifications')
            ->where('user_id', $validated['user_id'])
            ->where(function ($q) {
                $q->where('is_read', false)->orWhere('read', false);
            })
            ->update([
                'is_read' => true,
                'read' => true,
                'read_at' => now(),
                'updated_at' => now(),
            ]);

        return response()->json(['success' => true]);
    }

    /**
     * Delete a notification.
     */
    public function destroy(string $id)
    {
        $deleted = DB::table('user_notifications')->where('id', $id)->delete();
        if (! $deleted) {
            return response()->json(['success' => false, 'error' => 'Notification not found'], 404);
        }

        return response()->json(['success' => true]);
    }
}
