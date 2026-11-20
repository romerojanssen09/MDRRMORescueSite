<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmergencyReport;
use App\Models\User;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MobileMessageController extends Controller
{
    public function __construct(private readonly PushNotificationService $pushService)
    {
    }

    /**
     * Return report conversations for a mobile user (citizen or rescuer).
     */
    public function conversations(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|uuid|exists:users,id',
        ]);

        $user = User::findOrFail($validated['user_id']);
        $reports = $this->accessibleReportsQuery($user)
            ->with([
                'citizen:id,full_name,email,role',
                'assignedRescuer:id,full_name,email,role',
                'assignedTeam:id,team_name',
            ])
            ->whereIn('status', ['In Progress', 'Completed'])
            ->orderByDesc('updated_at')
            ->limit(100)
            ->get();

        $reportIds = $reports->pluck('id')->all();
        if ($reportIds === []) {
            return response()->json(['success' => true, 'conversations' => []]);
        }

        $latestMessages = DB::table('messages')
            ->whereIn('report_id', $reportIds)
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('report_id');

        $readIds = DB::table('message_reads')
            ->where('user_id', $user->id)
            ->pluck('message_id')
            ->flip();

        $conversations = $reports->map(function ($report) use ($user, $latestMessages, $readIds) {
            $msgs = $latestMessages->get($report->id, collect());
            $lastMessage = $msgs->first();

            $unreadCount = $msgs->filter(function ($m) use ($user, $readIds) {
                return $m->sender_id !== $user->id && ! isset($readIds[$m->id]);
            })->count();

            $otherUser = null;
            if ($user->role === 'citizen') {
                $otherUser = $report->assignedRescuer
                    ?: ($report->assignedTeam ? ['full_name' => $report->assignedTeam->team_name, 'role' => 'rescuer'] : null);
            } else {
                $otherUser = $report->citizen
                    ?: ['full_name' => $report->guest_name ?: 'Guest Citizen', 'role' => 'citizen'];
            }

            return [
                'report_id' => $report->id,
                'emergency_type' => $report->emergency_type,
                'location' => $report->location,
                'status' => $report->status,
                'show_name' => (bool) $report->show_name,
                'other_user' => $otherUser,
                'last_message' => $lastMessage,
                'unread_count' => $unreadCount,
                'updated_at' => $lastMessage->created_at ?? optional($report->updated_at)->toISOString(),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'conversations' => $conversations,
        ]);
    }

    /**
     * Return per-report and total unread counts for a user.
     */
    public function unreadSummary(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|uuid|exists:users,id',
        ]);

        $user = User::findOrFail($validated['user_id']);
        $reportIds = $this->accessibleReportsQuery($user)->pluck('id')->all();

        if ($reportIds === []) {
            return response()->json([
                'success' => true,
                'total_unread' => 0,
                'by_report' => (object) [],
            ]);
        }

        $readIds = DB::table('message_reads')
            ->where('user_id', $user->id)
            ->pluck('message_id')
            ->flip();

        $messages = DB::table('messages')
            ->select(['id', 'report_id', 'sender_id'])
            ->whereIn('report_id', $reportIds)
            ->where('sender_id', '!=', $user->id)
            ->get();

        $byReport = [];
        $total = 0;

        foreach ($messages as $m) {
            if (! isset($readIds[$m->id])) {
                $byReport[$m->report_id] = ($byReport[$m->report_id] ?? 0) + 1;
                $total++;
            }
        }

        return response()->json([
            'success' => true,
            'total_unread' => $total,
            'by_report' => (object) $byReport,
        ]);
    }

    /**
     * Get all messages for a specific report along with report context.
     */
    public function index(string $reportId)
    {
        $report = EmergencyReport::with([
            'citizen:id,full_name,phone,email,role',
            'assignedRescuer:id,full_name,phone,email,role',
            'assignedTeam:id,team_name,specialization,status',
        ])->find($reportId);

        if (! $report) {
            return response()->json(['success' => false, 'error' => 'Report not found'], 404);
        }

        $messages = DB::table('messages')
            ->leftJoin('users', 'messages.sender_id', '=', 'users.id')
            ->where('messages.report_id', $reportId)
            ->orderBy('messages.created_at', 'asc')
            ->select([
                'messages.id',
                'messages.report_id',
                'messages.sender_id',
                'messages.message',
                'messages.is_read',
                'messages.created_at',
                'messages.updated_at',
                'users.full_name as sender_name',
                'users.role as sender_role',
            ])
            ->get()
            ->map(function ($row) {
                $row->sender = [
                    'id' => $row->sender_id,
                    'full_name' => $row->sender_name,
                    'role' => $row->sender_role,
                ];

                return $row;
            });

        return response()->json([
            'success' => true,
            'report' => $report,
            'messages' => $messages,
        ]);
    }

    /**
     * Store a new chat message on a report and notify the other party.
     */
    public function store(Request $request, string $reportId)
    {
        $validated = $request->validate([
            'sender_id' => 'required|uuid|exists:users,id',
            'message' => 'required|string|max:2000',
        ]);

        $report = EmergencyReport::findOrFail($reportId);
        $sender = User::findOrFail($validated['sender_id']);
        $messageId = (string) Str::uuid();
        $now = now();

        DB::table('messages')->insert([
            'id' => $messageId,
            'report_id' => $report->id,
            'sender_id' => $sender->id,
            'message' => trim($validated['message']),
            'is_read' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Notify the other party via Expo push
        $recipientIds = [];
        if ($sender->id === $report->citizen_id) {
            if ($report->assigned_rescuer_id) {
                $recipientIds[] = $report->assigned_rescuer_id;
            } elseif ($report->assigned_team_id) {
                $recipientIds = User::where('rescue_team_id', $report->assigned_team_id)->pluck('id')->all();
            }
        } elseif ($report->citizen_id) {
            $recipientIds[] = $report->citizen_id;
        }

        foreach (array_unique($recipientIds) as $recipientId) {
            if ($recipientId !== $sender->id) {
                $this->pushService->sendToUser(
                    $recipientId,
                    "💬 Message from {$sender->full_name}",
                    Str::limit(trim($validated['message']), 100),
                    'new_message',
                    $report->id,
                    ['reportId' => $report->id, 'senderId' => $sender->id]
                );
            }
        }

        return response()->json([
            'success' => true,
            'message' => [
                'id' => $messageId,
                'report_id' => $report->id,
                'sender_id' => $sender->id,
                'message' => trim($validated['message']),
                'is_read' => false,
                'created_at' => $now->toISOString(),
                'sender' => [
                    'id' => $sender->id,
                    'full_name' => $sender->full_name,
                    'role' => $sender->role,
                ],
            ],
        ], 201);
    }

    /**
     * Mark all messages from other users in a report as read by user_id.
     */
    public function markRead(Request $request, string $reportId)
    {
        $validated = $request->validate([
            'user_id' => 'required|uuid|exists:users,id',
        ]);

        $userId = $validated['user_id'];
        $unreadMessageIds = DB::table('messages')
            ->where('report_id', $reportId)
            ->where('sender_id', '!=', $userId)
            ->whereNotExists(function ($q) use ($userId) {
                $q->select(DB::raw(1))
                    ->from('message_reads')
                    ->whereColumn('message_reads.message_id', 'messages.id')
                    ->where('message_reads.user_id', $userId);
            })
            ->pluck('id');

        $now = now();
        $rows = $unreadMessageIds->map(fn ($msgId) => [
            'id' => (string) Str::uuid(),
            'message_id' => $msgId,
            'user_id' => $userId,
            'read_at' => $now,
            'created_at' => $now,
        ])->all();

        if ($rows !== []) {
            DB::table('message_reads')->insertOrIgnore($rows);
        }

        return response()->json([
            'success' => true,
            'marked_count' => count($rows),
        ]);
    }

    private function accessibleReportsQuery(User $user)
    {
        if ($user->role === 'citizen') {
            return EmergencyReport::where('citizen_id', $user->id);
        }

        return EmergencyReport::where(function ($q) use ($user) {
            $q->where('assigned_rescuer_id', $user->id);
            if ($user->rescue_team_id) {
                $q->orWhere('assigned_team_id', $user->rescue_team_id);
            }
        });
    }
}
