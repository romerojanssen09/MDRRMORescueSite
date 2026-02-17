<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RescueTeam;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;

class RescueTeamController extends Controller
{
    public function index(Request $request)
    {
        $query = RescueTeam::with('members:id,full_name,email,rescue_team_id')
            ->select('id', 'team_name', 'specialization', 'status', 'members_count', 'created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('team_name', 'ilike', '%' . $request->search . '%')
                  ->orWhere('specialization', 'ilike', '%' . $request->search . '%');
            });
        }

        $teams = $query->latest()->paginate(20);

        return view('admin.teams.index', compact('teams'));
    }

    public function create()
    {
        $availableRescuers = \App\Models\User::where('role', 'rescuer')
            ->whereNull('rescue_team_id')
            ->get();
        return view('admin.teams.create', compact('availableRescuers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'team_name' => 'required|string|max:255',
            'specialization' => 'required|string|max:255',
            'province' => 'required|string|max:255',
            'municipality' => 'required|string|max:255',
            'barangay' => 'required|string|max:255',
            'street_address' => 'nullable|string|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'member_ids' => 'required|array|min:1',
            'member_ids.*' => 'exists:users,id',
        ]);

        // Create the team with default status 'Available'
        $team = RescueTeam::create([
            'team_name' => $validated['team_name'],
            'specialization' => $validated['specialization'],
            'status' => 'Available',
            'province' => $validated['province'],
            'municipality' => $validated['municipality'],
            'barangay' => $validated['barangay'],
            'street_address' => $validated['street_address'],
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'members_count' => count($validated['member_ids']),
        ]);

        // Get the users being added to the team
        $newMembers = \App\Models\User::whereIn('id', $validated['member_ids'])->get();

        // Update selected rescuers with the team_id
        \App\Models\User::whereIn('id', $validated['member_ids'])
            ->update(['rescue_team_id' => $team->id]);

        // Send push notifications to new team members
        $pushService = new PushNotificationService();
        foreach ($newMembers as $member) {
            // Get active push tokens from push_tokens table (used by mobile app)
            $pushTokens = \DB::table('push_tokens')
                ->where('user_id', $member->id)
                ->where('is_active', true)
                ->get();
            
            foreach ($pushTokens as $tokenRecord) {
                $pushService->sendNotification(
                    $tokenRecord->push_token,
                    'Added to Rescue Team',
                    "You have been added to {$team->team_name} ({$team->specialization})",
                    ['type' => 'team_assignment', 'team_id' => $team->id]
                );
            }
        }

        return redirect()->route('admin.teams.index')
            ->with('success', 'Rescue team created successfully with ' . count($validated['member_ids']) . ' members');
    }

    public function show(RescueTeam $team)
    {
        return view('admin.teams.show', compact('team'));
    }

    public function edit(RescueTeam $team)
    {
        $team->load('members');
        $availableRescuers = \App\Models\User::where('role', 'rescuer')
            ->where(function($q) use ($team) {
                $q->whereNull('rescue_team_id')
                  ->orWhere('rescue_team_id', $team->id);
            })
            ->get();
        return view('admin.teams.edit', compact('team', 'availableRescuers'));
    }

    public function update(Request $request, RescueTeam $team)
    {
        $validated = $request->validate([
            'team_name' => 'required|string|max:255',
            'specialization' => 'required|string|max:255',
            'status' => 'required|in:Available,On Mission,Off Duty',
            'province' => 'required|string|max:255',
            'municipality' => 'required|string|max:255',
            'barangay' => 'required|string|max:255',
            'street_address' => 'nullable|string|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'member_ids' => 'required|array|min:1',
            'member_ids.*' => 'exists:users,id',
        ]);

        // Get old member IDs before update
        $oldMemberIds = $team->members->pluck('id')->toArray();
        
        // Find newly added members (in new list but not in old list)
        $newMemberIds = array_diff($validated['member_ids'], $oldMemberIds);

        // Update team details
        $team->update([
            'team_name' => $validated['team_name'],
            'specialization' => $validated['specialization'],
            'status' => $validated['status'],
            'province' => $validated['province'],
            'municipality' => $validated['municipality'],
            'barangay' => $validated['barangay'],
            'street_address' => $validated['street_address'],
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'members_count' => count($validated['member_ids']),
        ]);

        // Remove team_id from old members not in the new list
        \App\Models\User::where('rescue_team_id', $team->id)
            ->whereNotIn('id', $validated['member_ids'])
            ->update(['rescue_team_id' => null]);

        // Add team_id to new members
        \App\Models\User::whereIn('id', $validated['member_ids'])
            ->update(['rescue_team_id' => $team->id]);

        // Send push notifications to newly added members only
        if (!empty($newMemberIds)) {
            $newMembers = \App\Models\User::whereIn('id', $newMemberIds)->get();
            $pushService = new PushNotificationService();
            
            foreach ($newMembers as $member) {
                // Get active push tokens from push_tokens table (used by mobile app)
                $pushTokens = \DB::table('push_tokens')
                    ->where('user_id', $member->id)
                    ->where('is_active', true)
                    ->get();
                
                foreach ($pushTokens as $tokenRecord) {
                    $pushService->sendNotification(
                        $tokenRecord->push_token,
                        'Added to Rescue Team',
                        "You have been added to {$team->team_name} ({$team->specialization})",
                        ['type' => 'team_assignment', 'team_id' => $team->id]
                    );
                }
            }
        }

        return redirect()->route('admin.teams.index')
            ->with('success', 'Rescue team updated successfully with ' . count($validated['member_ids']) . ' members');
    }

    public function destroy(RescueTeam $team)
    {
        $team->delete();
        return redirect()->route('admin.teams.index')
            ->with('success', 'Rescue team deleted successfully');
    }

    public function reassignMembers(Request $request, RescueTeam $team)
    {
        $validated = $request->validate([
            'member_ids' => 'required|array|min:1',
            'member_ids.*' => 'exists:users,id',
            'destination_team_id' => 'required|exists:rescue_teams,id',
        ]);

        $destinationTeam = RescueTeam::findOrFail($validated['destination_team_id']);
        
        // Verify members belong to the source team
        $members = \App\Models\User::whereIn('id', $validated['member_ids'])
            ->where('rescue_team_id', $team->id)
            ->get();

        if ($members->isEmpty()) {
            return back()->with('error', 'No valid members selected for reassignment');
        }

        // Reassign members to destination team
        \App\Models\User::whereIn('id', $validated['member_ids'])
            ->update(['rescue_team_id' => $destinationTeam->id]);

        // Update member counts
        $team->update(['members_count' => $team->members()->count()]);
        $destinationTeam->update(['members_count' => $destinationTeam->members()->count()]);

        // Send push notifications to reassigned members
        $pushService = new PushNotificationService();
        foreach ($members as $member) {
            $pushTokens = \DB::table('push_tokens')
                ->where('user_id', $member->id)
                ->where('is_active', true)
                ->get();
            
            foreach ($pushTokens as $tokenRecord) {
                $pushService->sendNotification(
                    $tokenRecord->push_token,
                    'Team Reassignment',
                    "You have been reassigned from {$team->team_name} to {$destinationTeam->team_name}",
                    ['type' => 'team_reassignment', 'team_id' => $destinationTeam->id]
                );
            }
        }

        return redirect()->route('admin.teams.show', $team)
            ->with('success', count($validated['member_ids']) . ' member(s) reassigned to ' . $destinationTeam->team_name);
    }
}
