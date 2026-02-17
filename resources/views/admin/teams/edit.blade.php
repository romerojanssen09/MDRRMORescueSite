@extends('layouts.admin')

@section('title', 'Edit Rescue Team')
@section('header', 'Edit Rescue Team')
@section('subtitle', 'Update team information and manage members')

@section('content')
<div class="max-w-5xl mx-auto">
    <form action="{{ route('admin.teams.update', $team) }}" method="POST" id="teamForm">
        @csrf
        @method('PUT')

        <!-- Basic Information -->
        <div class="card p-6 mb-6 border border-primary-700">
            <h3 class="text-sm font-semibold text-primary-100 mb-4">Basic Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs text-primary-400 mb-2">Team Name *</label>
                    <input type="text" name="team_name" value="{{ old('team_name', $team->team_name) }}" class="input-field text-sm @error('team_name') border-accent-400 @enderror" required>
                    @error('team_name')
                        <p class="text-accent-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs text-primary-400 mb-2">Specialization *</label>
                    <input type="text" name="specialization" value="{{ old('specialization', $team->specialization) }}" class="input-field text-sm @error('specialization') border-accent-400 @enderror" placeholder="e.g., Fire & Rescue, Medical" required>
                    @error('specialization')
                        <p class="text-accent-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs text-primary-400 mb-2">Status *</label>
                    <select name="status" class="input-field text-sm @error('status') border-accent-400 @enderror" required>
                        <option value="Available" {{ old('status', $team->status) === 'Available' ? 'selected' : '' }}>Available</option>
                        <option value="On Mission" {{ old('status', $team->status) === 'On Mission' ? 'selected' : '' }}>On Mission</option>
                        <option value="Off Duty" {{ old('status', $team->status) === 'Off Duty' ? 'selected' : '' }}>Off Duty</option>
                    </select>
                    @error('status')
                        <p class="text-accent-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Location -->
        <div class="card p-6 mb-6 border border-primary-700">
            <h3 class="text-sm font-semibold text-primary-100 mb-4">
                <i class="fas fa-map-marker-alt text-secondary-400 mr-2"></i>Team Location
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="block text-xs text-primary-400 mb-2">Province *</label>
                    <input type="text" name="province" value="{{ old('province', $team->province) }}" class="input-field text-sm" required>
                </div>
                <div>
                    <label class="block text-xs text-primary-400 mb-2">Municipality *</label>
                    <input type="text" name="municipality" value="{{ old('municipality', $team->municipality) }}" class="input-field text-sm" required>
                </div>
                <div>
                    <label class="block text-xs text-primary-400 mb-2">Barangay *</label>
                    <input type="text" name="barangay" value="{{ old('barangay', $team->barangay) }}" class="input-field text-sm" required>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-xs text-primary-400 mb-2">Street Address (Optional)</label>
                <input type="text" name="street_address" value="{{ old('street_address', $team->street_address) }}" class="input-field text-sm" placeholder="Street, Building, etc.">
            </div>

            <div>
                <label class="block text-xs text-primary-400 mb-2">Click on map to update coordinates *</label>
                <div id="map" style="height: 350px;" class="rounded-lg border border-primary-700 mb-2"></div>
                <input type="hidden" name="latitude" id="latitude" value="{{ old('latitude', $team->latitude) }}">
                <input type="hidden" name="longitude" id="longitude" value="{{ old('longitude', $team->longitude) }}">
                <p class="text-xs text-primary-500">
                    <span id="coordinates-display">
                        @if($team->latitude && $team->longitude)
                            Current: {{ number_format($team->latitude, 6) }}, {{ number_format($team->longitude, 6) }}
                        @else
                            Click on the map to set coordinates
                        @endif
                    </span>
                </p>
            </div>
        </div>

        <!-- Team Members -->
        <div class="card p-6 mb-6 border border-primary-700">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-primary-100">
                    <i class="fas fa-users text-secondary-400 mr-2"></i>Team Members
                </h3>
                <span class="text-xs text-primary-400">
                    <span id="member-count">{{ $team->members->count() }}</span> selected
                </span>
            </div>
            
            <div class="bg-primary-900 border border-primary-700 rounded-lg p-4 mb-4">
                <p class="text-xs text-primary-300">
                    <i class="fas fa-info-circle text-secondary-400 mr-1"></i>
                    Check rescuers to add them to this team. Uncheck to remove them and make them available for other teams.
                </p>
            </div>

            <div class="space-y-2 max-h-96 overflow-y-auto">
                @forelse($availableRescuers as $rescuer)
                    @php
                        $isCurrentMember = $rescuer->rescue_team_id === $team->id;
                        $isChecked = in_array($rescuer->id, old('member_ids', $team->members->pluck('id')->toArray()));
                    @endphp
                    <label class="flex items-center p-3 bg-primary-800 hover:bg-primary-700 rounded-lg cursor-pointer transition border border-primary-700 {{ $isCurrentMember ? 'border-secondary-900' : '' }}">
                        <input type="checkbox" 
                               name="member_ids[]" 
                               value="{{ $rescuer->id }}" 
                               {{ $isChecked ? 'checked' : '' }}
                               class="w-4 h-4 text-secondary-400 bg-primary-700 border-primary-600 rounded focus:ring-secondary-400 focus:ring-2 member-checkbox">
                        <div class="ml-3 flex-1">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-primary-100 font-medium">{{ $rescuer->full_name }}</span>
                                @if($isCurrentMember)
                                    <span class="px-2 py-0.5 text-xs rounded-md bg-secondary-900 text-secondary-300 font-medium">
                                        <i class="fas fa-check-circle mr-1"></i>Current Member
                                    </span>
                                @endif
                            </div>
                            <p class="text-xs text-primary-400 mt-0.5">{{ $rescuer->email }}</p>
                        </div>
                    </label>
                @empty
                    <div class="text-center py-8">
                        <i class="fas fa-user-slash text-3xl text-primary-700 mb-2"></i>
                        <p class="text-sm text-primary-500">No rescuers available</p>
                    </div>
                @endforelse
            </div>
            
            @error('member_ids')
                <p class="text-accent-400 text-xs mt-2">{{ $message }}</p>
            @enderror
            
            @if($team->members->count() > 0)
            <div class="mt-4 p-3 bg-accent-900 bg-opacity-20 border border-accent-900 rounded-lg">
                <p class="text-xs text-accent-300">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    Unchecking members will remove them from this team and make them available for reassignment
                </p>
            </div>
            @endif
        </div>

        <!-- Actions -->
        <div class="flex gap-3">
            <button type="submit" id="submitBtn" class="bg-secondary-400 hover:bg-secondary-500 text-primary-950 px-6 py-2.5 rounded-lg transition text-sm font-medium">
                <i class="fas fa-save mr-2"></i><span id="btnText">Update Team</span>
            </button>
            <a href="{{ route('admin.teams.index') }}" class="bg-primary-700 hover:bg-primary-600 text-primary-200 px-6 py-2.5 rounded-lg transition text-sm">
                <i class="fas fa-times mr-2"></i>Cancel
            </a>
            <button type="button" onclick="if(confirm('Delete this team? Members will be unassigned.')) document.getElementById('deleteForm').submit();" class="ml-auto bg-accent-900 hover:bg-accent-800 text-accent-300 px-6 py-2.5 rounded-lg transition text-sm">
                <i class="fas fa-trash mr-2"></i>Delete Team
            </button>
        </div>
    </form>

    <!-- Delete Form -->
    <form id="deleteForm" action="{{ route('admin.teams.destroy', $team) }}" method="POST" class="hidden">
        @csrf
        @method('DELETE')
    </form>
</div>

<link href='https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css' rel='stylesheet' />
<script src='https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js'></script>

<style>
.mapboxgl-ctrl-logo, .mapboxgl-ctrl-attrib, .mapboxgl-compact { display: none !important; }
</style>

<script>
document.getElementById('teamForm').addEventListener('submit', function() {
    const btn = document.getElementById('submitBtn');
    const btnText = document.getElementById('btnText');
    btn.disabled = true;
    btn.classList.add('opacity-50');
    btnText.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Updating...';
});

mapboxgl.accessToken = '{{ env('MAPBOX_ACCESS_TOKEN') }}';
const map = new mapboxgl.Map({
    container: 'map',
    style: 'mapbox://styles/mapbox/dark-v11',
    center: [{{ $team->longitude ?? 122.826 }}, {{ $team->latitude ?? 10.465 }}],
    zoom: 12,
    attributionControl: false
});

let marker = null;

@if($team->latitude && $team->longitude)
marker = new mapboxgl.Marker({ color: '#FF6B6B' })
    .setLngLat([{{ $team->longitude }}, {{ $team->latitude }}])
    .addTo(map);
@endif

map.on('click', (e) => {
    const { lng, lat } = e.lngLat;
    document.getElementById('latitude').value = lat.toFixed(8);
    document.getElementById('longitude').value = lng.toFixed(8);
    document.getElementById('coordinates-display').textContent = `Coordinates: ${lat.toFixed(6)}, ${lng.toFixed(6)}`;
    
    if (marker) marker.remove();
    marker = new mapboxgl.Marker({ color: '#FF6B6B' }).setLngLat([lng, lat]).addTo(map);
});

map.addControl(new mapboxgl.NavigationControl());

// Update member count when checkboxes change
document.querySelectorAll('.member-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const count = document.querySelectorAll('.member-checkbox:checked').length;
        document.getElementById('member-count').textContent = count;
    });
});
</script>
@endsection
