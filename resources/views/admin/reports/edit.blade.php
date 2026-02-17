@extends('layouts.admin')

@section('title', 'Assign Rescuer')
@section('header', 'Assign Rescuer to Emergency Report')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow p-6">
        <!-- Report Details -->
        <div class="mb-6 p-4 bg-gray-50 rounded">
            <h3 class="font-bold text-lg mb-2">Report Details</h3>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="text-gray-600">Type:</span>
                    <span class="font-semibold ml-2">{{ $report->emergency_type }}</span>
                </div>
                <div>
                    <span class="text-gray-600">Status:</span>
                    <span class="ml-2 px-2 py-1 text-xs rounded-full 
                        {{ $report->status === 'Pending' ? 'bg-orange-100 text-orange-800' : '' }}
                        {{ $report->status === 'In Progress' ? 'bg-teal-100 text-teal-800' : '' }}
                        {{ $report->status === 'Completed' ? 'bg-green-100 text-green-800' : '' }}">
                        {{ $report->status }}
                    </span>
                </div>
                <div class="col-span-2">
                    <span class="text-gray-600">Location:</span>
                    <span class="ml-2">{{ $report->location }}</span>
                </div>
                @if($report->citizen)
                <div class="col-span-2">
                    <span class="text-gray-600">Citizen:</span>
                    <span class="ml-2">{{ $report->citizen->full_name }} ({{ $report->citizen->phone }})</span>
                </div>
                @endif
            </div>
        </div>

        <form action="{{ route('admin.reports.update', $report) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">
                    Assignment Type <span class="text-red-500">*</span>
                </label>
                <div class="flex gap-4 mb-2">
                    <label class="flex items-center">
                        <input type="radio" name="assignment_type" value="rescuer" class="mr-2" 
                            {{ old('assignment_type', $report->assigned_rescuer_id ? 'rescuer' : 'team') === 'rescuer' ? 'checked' : '' }}
                            onchange="toggleAssignment()">
                        <span>Assign Individual Rescuer</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="assignment_type" value="team" class="mr-2"
                            {{ old('assignment_type', $report->assigned_team_id ? 'team' : '') === 'team' ? 'checked' : '' }}
                            onchange="toggleAssignment()">
                        <span>Assign Rescue Team</span>
                    </label>
                </div>
            </div>

            <div id="rescuer-section" class="mb-4" style="display: none;">
                <label class="block text-gray-700 text-sm font-bold mb-2">
                    Select Rescuer <span class="text-red-500">*</span>
                </label>
                <select name="assigned_rescuer_id" class="w-full border rounded px-3 py-2 @error('assigned_rescuer_id') border-red-500 @enderror">
                    <option value="">-- Select Rescuer --</option>
                    @foreach($rescuers as $rescuer)
                        <option value="{{ $rescuer->id }}" {{ $report->assigned_rescuer_id === $rescuer->id ? 'selected' : '' }}>
                            {{ $rescuer->full_name }} ({{ $rescuer->email }})
                        </option>
                    @endforeach
                </select>
                @error('assigned_rescuer_id')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div id="team-section" class="mb-4" style="display: none;">
                <label class="block text-gray-700 text-sm font-bold mb-2">
                    Select Rescue Team <span class="text-red-500">*</span>
                </label>
                <select name="assigned_team_id" class="w-full border rounded px-3 py-2 @error('assigned_team_id') border-red-500 @enderror">
                    <option value="">-- Select Team --</option>
                    @foreach($teams as $team)
                        <option value="{{ $team->id }}" {{ $report->assigned_team_id === $team->id ? 'selected' : '' }}>
                            {{ $team->name }} - {{ $team->specialization }} ({{ $team->status }})
                        </option>
                    @endforeach
                </select>
                @error('assigned_team_id')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
                
                @if($report->assigned_team_id)
                <p class="text-sm text-blue-600 mt-2">
                    <i class="fas fa-info-circle"></i> Currently assigned to team: {{ $report->assignedTeam->name ?? 'Unknown' }}
                </p>
                @endif
            </div>

            <div class="bg-yellow-50 border border-yellow-200 rounded p-4 mb-4">
                <p class="text-sm text-yellow-800">
                    <i class="fas fa-bell"></i> <strong>Notifications will be sent to:</strong>
                </p>
                <ul class="text-sm text-yellow-700 mt-2 ml-4 list-disc" id="notification-info">
                    <li>Citizen: "A rescuer/team has been assigned to your emergency"</li>
                    <li id="rescuer-notif">Rescuer: "You have been assigned to a new emergency"</li>
                    <li id="team-notif" style="display: none;">All team members (who are logged in): "Your team has been assigned to a new emergency"</li>
                </ul>
            </div>

            <div class="flex gap-4">
                <button type="submit" class="bg-blue-500 text-white px-6 py-2 rounded hover:bg-blue-600">
                    <i class="fas fa-user-plus"></i> Assign
                </button>
                <a href="{{ route('admin.reports.assign-map', $report) }}" class="bg-green-500 text-white px-6 py-2 rounded hover:bg-green-600">
                    <i class="fas fa-map-marked-alt"></i> View on Map
                </a>
                <a href="{{ route('admin.reports.show', $report) }}" class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>

        <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded">
            <p class="text-sm text-blue-800">
                <i class="fas fa-info-circle"></i> <strong>Note:</strong> Only rescuers can mark reports as "Completed". Admin can only assign rescuers or teams.
            </p>
        </div>
    </div>
</div>

<script>
function toggleAssignment() {
    const assignmentType = document.querySelector('input[name="assignment_type"]:checked').value;
    const rescuerSection = document.getElementById('rescuer-section');
    const teamSection = document.getElementById('team-section');
    const rescuerNotif = document.getElementById('rescuer-notif');
    const teamNotif = document.getElementById('team-notif');
    
    if (assignmentType === 'rescuer') {
        rescuerSection.style.display = 'block';
        teamSection.style.display = 'none';
        rescuerNotif.style.display = 'list-item';
        teamNotif.style.display = 'none';
        
        // Make rescuer required, team optional
        document.querySelector('select[name="assigned_rescuer_id"]').required = true;
        document.querySelector('select[name="assigned_team_id"]').required = false;
    } else {
        rescuerSection.style.display = 'none';
        teamSection.style.display = 'block';
        rescuerNotif.style.display = 'none';
        teamNotif.style.display = 'list-item';
        
        // Make team required, rescuer optional
        document.querySelector('select[name="assigned_rescuer_id"]').required = false;
        document.querySelector('select[name="assigned_team_id"]').required = true;
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleAssignment();
});
</script>
@endsection
