<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmergencyReport;
use App\Models\User;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;

class EmergencyReportController extends Controller
{
    protected $pushNotificationService;

    public function __construct(PushNotificationService $pushNotificationService)
    {
        $this->pushNotificationService = $pushNotificationService;
    }
    public function index(Request $request)
    {
        $query = EmergencyReport::with(['citizen:id,full_name', 'assignedRescuer:id,full_name', 'assignedTeam:id,team_name'])
            ->select('id', 'emergency_type', 'location', 'status', 'citizen_id', 'assigned_rescuer_id', 'assigned_team_id', 'created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('emergency_type')) {
            $query->where('emergency_type', $request->emergency_type);
        }

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('location', 'ilike', '%' . $request->search . '%')
                  ->orWhere('description', 'ilike', '%' . $request->search . '%');
            });
        }

        // Order by created_at descending (newest first)
        $reports = $query->orderBy('created_at', 'desc')->paginate(20);
        
        // Only load rescuers when needed (not on every page load)
        $rescuers = $request->has('assign') 
            ? User::where('role', 'rescuer')->select('id', 'full_name')->get()
            : collect();

        return view('admin.reports.index', compact('reports', 'rescuers'));
    }

    public function show(EmergencyReport $report)
    {
        $report->load(['citizen', 'assignedRescuer', 'assignedTeam', 'messages.sender']);
        return view('admin.reports.show', compact('report'));
    }

    public function edit(EmergencyReport $report)
    {
        // Redirect to assign-map instead of showing edit page
        return redirect()->route('admin.reports.assign-map', $report);
    }

    public function assignMap(EmergencyReport $report)
    {
        $report->load('citizen');
        
        // Get all teams with location data (Available and On Mission)
        $teams = \App\Models\RescueTeam::with('members')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();
        
        // Calculate distance for each team using routing API
        $reportLat = $report->latitude ?? 10.465;
        $reportLng = $report->longitude ?? 122.826;
        $mapboxToken = env('MAPBOX_ACCESS_TOKEN');
        
        $teams = $teams->map(function($team) use ($reportLat, $reportLng, $mapboxToken) {
            // Try to get route distance from Mapbox Directions API
            if ($mapboxToken) {
                try {
                    $url = "https://api.mapbox.com/directions/v5/mapbox/driving/{$team->longitude},{$team->latitude};{$reportLng},{$reportLat}?access_token={$mapboxToken}&geometries=geojson";
                    
                    $response = @file_get_contents($url);
                    if ($response) {
                        $data = json_decode($response, true);
                        
                        if (isset($data['routes'][0]['distance'])) {
                            // Distance in meters, convert to km
                            $team->distance = round($data['routes'][0]['distance'] / 1000, 2);
                            $team->duration = isset($data['routes'][0]['duration']) ? round($data['routes'][0]['duration'] / 60) : null; // minutes
                            $team->route_type = 'driving'; // Indicates this is actual route distance
                            return $team;
                        }
                    }
                } catch (\Exception $e) {
                    // Fallback to straight-line distance on error
                }
            }
            
            // Fallback to straight-line distance
            $team->distance = $this->calculateDistance($reportLat, $reportLng, $team->latitude, $team->longitude);
            $team->duration = null;
            $team->route_type = 'straight';
            return $team;
        });
        
        // Sort teams: Available first (by distance), then Deployed (by distance)
        $teams = $teams->sortBy(function($team) {
            // Available teams get priority (0-999), Deployed teams go to bottom (1000+)
            $statusPriority = $team->status === 'Available' ? 0 : 1000;
            return $statusPriority + $team->distance;
        })->values();
            
        return view('admin.reports.assign-map', compact('report', 'teams'));
    }
    
    /**
     * Calculate distance between two coordinates using Haversine formula
     * Returns distance in kilometers
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon/2) * sin($dLon/2);

        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        $distance = $earthRadius * $c;

        return round($distance, 2); // Return distance rounded to 2 decimal places
    }

    public function update(Request $request, EmergencyReport $report)
    {
        $validated = $request->validate([
            'assignment_type' => 'required|in:rescuer,team',
            'assigned_rescuer_id' => 'required_if:assignment_type,rescuer|nullable|exists:users,id',
            'assigned_team_id' => 'required_if:assignment_type,team|nullable|exists:rescue_teams,id',
        ]);

        // Store old values for comparison
        $oldRescuerId = $report->assigned_rescuer_id;
        $oldTeamId = $report->assigned_team_id;

        // Admin can only assign rescuers or teams, not change status
        // Status will be automatically set to "In Progress" when assigned
        if ($validated['assignment_type'] === 'rescuer') {
            $report->update([
                'assigned_rescuer_id' => $validated['assigned_rescuer_id'],
                'assigned_team_id' => null,
                'status' => 'In Progress',
            ]);

            // Send notifications if rescuer was assigned or changed
            if ($validated['assigned_rescuer_id'] !== $oldRescuerId) {
                $rescuer = User::find($validated['assigned_rescuer_id']);
                if ($rescuer) {
                    $this->pushNotificationService->notifyRescuerAssigned($report, $rescuer);
                }
            }

            return redirect()->route('admin.reports.index')
                ->with('success', 'Rescuer assigned successfully. Notifications sent to citizen and rescuer.');
        } else {
            // Team assignment
            $report->update([
                'assigned_team_id' => $validated['assigned_team_id'],
                'assigned_rescuer_id' => null,
                'status' => 'In Progress',
            ]);

            // Send notifications if team was assigned or changed
            if ($validated['assigned_team_id'] !== $oldTeamId) {
                $team = \App\Models\RescueTeam::find($validated['assigned_team_id']);
                if ($team) {
                    // Update team status to On Mission
                    $team->update(['status' => 'On Mission']);
                    
                    // Send notifications
                    $this->pushNotificationService->notifyTeamAssigned($report, $team);
                }
                
                // If there was a previous team, set them back to Available
                if ($oldTeamId && $oldTeamId !== $validated['assigned_team_id']) {
                    $oldTeam = \App\Models\RescueTeam::find($oldTeamId);
                    if ($oldTeam) {
                        $oldTeam->update(['status' => 'Available']);
                    }
                }
            }

            return redirect()->route('admin.reports.index')
                ->with('success', 'Rescue team assigned successfully. Team status updated to On Mission. Notifications sent to citizen and all team members.');
        }
    }

    public function destroy(EmergencyReport $report)
    {
        $report->delete();
        return redirect()->route('admin.reports.index')
            ->with('success', 'Report deleted successfully');
    }
}
