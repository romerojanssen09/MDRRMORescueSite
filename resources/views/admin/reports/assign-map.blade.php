@extends('layouts.admin')

@section('title', 'Assign Team - Map View')
@section('header', 'Assign Team to Emergency Report')

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.reports.show', $report) }}" class="text-secondary-400 hover:text-secondary-300 transition">
        <i class="fas fa-arrow-left mr-2"></i> Back to Report
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Report Details -->
    <div class="lg:col-span-1">
        <div class="card border border-primary-700 p-5 sticky top-6">
            <h3 class="text-sm font-semibold text-primary-100 mb-4">
                <i class="fas fa-file-lines text-accent-400 mr-2"></i>Emergency Report
            </h3>
            
            <div class="space-y-3 text-sm">
                <div>
                    <span class="text-xs text-primary-400 block mb-1">Type:</span>
                    <span class="px-2 py-1 bg-accent-900 text-accent-300 rounded text-xs font-medium">
                        {{ $report->emergency_type }}
                    </span>
                </div>
                
                <div>
                    <span class="text-xs text-primary-400 block mb-1">Location:</span>
                    <p class="text-sm text-primary-200">{{ $report->location }}</p>
                </div>
                
                @if($report->citizen)
                <div>
                    <span class="text-xs text-primary-400 block mb-1">Citizen:</span>
                    <p class="text-sm text-primary-200">{{ $report->citizen->full_name }}</p>
                    <p class="text-xs text-primary-500">{{ $report->citizen->phone }}</p>
                </div>
                @endif
                
                <div>
                    <span class="text-xs text-primary-400 block mb-1">Status:</span>
                    <span class="px-2 py-1 bg-secondary-900 text-secondary-300 rounded text-xs font-medium">
                        {{ $report->status }}
                    </span>
                </div>
            </div>

            <div class="mt-6 pt-6 border-t border-primary-700">
                <div class="flex justify-between items-center mb-4">
                    <h4 class="text-sm font-semibold text-primary-100">
                        <i class="fas fa-users text-secondary-400 mr-2"></i>Available Teams
                    </h4>
                    <label class="flex items-center gap-2 text-xs cursor-pointer">
                        <input type="checkbox" id="showRoutesToggle" class="rounded bg-primary-700 border-primary-600 text-secondary-400 focus:ring-secondary-400" checked onchange="toggleRoutes()">
                        <span class="text-primary-300">Routes</span>
                    </label>
                </div>
                @if($report->assigned_team_id)
                    <div class="mb-3 p-3 bg-secondary-900/20 border border-secondary-900 rounded-lg">
                        <p class="text-xs text-secondary-300">
                            <i class="fas fa-check-circle mr-1"></i> Team already assigned
                        </p>
                    </div>
                @endif
                <div class="space-y-2 max-h-[500px] overflow-y-auto pr-2 custom-scrollbar">
                    @forelse($teams as $team)
                        <div id="team-card-{{ $team->id }}" 
                             class="p-3 border border-primary-700 rounded-lg hover:bg-primary-700 cursor-pointer transition-all {{ $team->status === 'On Mission' ? 'opacity-60' : '' }}"
                             onclick="selectTeamFromList('{{ $team->id }}', {{ $team->latitude }}, {{ $team->longitude }})">
                            <div class="flex justify-between items-start gap-3">
                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold text-sm text-primary-100 truncate">{{ $team->team_name }}</p>
                                    <p class="text-xs text-primary-400">{{ $team->specialization }}</p>
                                    <p class="text-xs text-primary-500 mt-1">{{ $team->members_count }} members</p>
                                    @if($team->status === 'On Mission')
                                        <span class="inline-block mt-1 px-2 py-0.5 bg-accent-900 text-accent-300 text-xs rounded">
                                            <i class="fas fa-ambulance"></i> On Mission
                                        </span>
                                    @endif
                                </div>
                                <div class="text-right flex flex-col items-end gap-2 flex-shrink-0">
                                    <span class="px-2 py-1 bg-secondary-900 text-secondary-300 text-xs rounded font-semibold">
                                        {{ $team->distance }} km
                                    </span>
                                    @if($report->assigned_team_id)
                                        @if($report->assigned_team_id == $team->id)
                                            <button disabled
                                                class="bg-secondary-400 text-primary-950 px-3 py-1 rounded text-xs whitespace-nowrap cursor-not-allowed">
                                                <i class="fas fa-check-circle"></i> Assigned
                                            </button>
                                        @else
                                            <button disabled
                                                class="bg-primary-600 text-primary-400 px-3 py-1 rounded text-xs whitespace-nowrap cursor-not-allowed">
                                                <i class="fas fa-ban"></i> Unavailable
                                            </button>
                                        @endif
                                    @elseif($team->status === 'On Mission')
                                        <button disabled
                                            class="bg-accent-900 text-accent-300 px-3 py-1 rounded text-xs whitespace-nowrap cursor-not-allowed">
                                            <i class="fas fa-clock"></i> Busy
                                        </button>
                                    @else
                                        <button onclick="event.stopPropagation(); showConfirmModal('{{ $team->id }}', '{{ $team->team_name }}', {{ $team->members_count }}, '{{ $team->specialization }}', {{ $team->distance }})" 
                                            class="bg-secondary-400 text-primary-950 px-3 py-1 rounded hover:bg-secondary-500 text-xs whitespace-nowrap transition">
                                            <i class="fas fa-check"></i> Assign
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-primary-500 text-center py-4">No available teams with location data</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Map -->
    <div class="lg:col-span-2">
        <div class="card border border-primary-700 p-5">
            <h3 class="text-sm font-semibold text-primary-100 mb-4">
                <i class="fas fa-map-location-dot text-secondary-400 mr-2"></i>Team Locations
            </h3>
            <div id="map" style="height: 600px;" class="rounded-lg border border-primary-700 overflow-hidden"></div>
            
            <div class="mt-4 p-4 bg-secondary-900/20 border border-secondary-900 rounded-lg">
                <p class="text-xs text-secondary-300">
                    <i class="fas fa-info-circle mr-1"></i> 
                    <strong>Legend:</strong> 
                    <span class="ml-2">🔴 Red = Rescue Teams</span>
                    <span class="ml-4">🔵 Blue = Emergency Location</span>
                </p>
            </div>

            <div class="mt-4 p-4 bg-primary-700 rounded-lg">
                <h4 class="font-semibold text-sm text-primary-100 mb-3">
                    <i class="fas fa-lightbulb text-secondary-400 mr-2"></i>How to Assign
                </h4>
                <ol class="text-xs text-primary-300 space-y-2 list-decimal list-inside">
                    <li>View team locations on the map (red markers)</li>
                    <li>Teams are sorted by distance (nearest first)</li>
                    <li>Click "Assign" button in the sidebar</li>
                    <li>Or click on a team marker on the map</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div id="confirmModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm hidden items-center justify-center z-50">
    <div class="card border border-primary-700 p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-bold text-primary-100 mb-4">Confirm Team Assignment</h3>
        <div id="modalContent"></div>
        <form id="assignForm" action="{{ route('admin.reports.update', $report) }}" method="POST">
            @csrf
            @method('PUT')
            <input type="hidden" name="assignment_type" value="team">
            <input type="hidden" name="assigned_team_id" id="selectedTeamId">
            
            <div class="flex gap-3 mt-6">
                <button type="submit" class="flex-1 bg-secondary-400 text-primary-950 px-4 py-2.5 rounded-lg hover:bg-secondary-500 transition font-medium text-sm">
                    <i class="fas fa-check mr-2"></i> Confirm Assignment
                </button>
                <button type="button" onclick="closeModal()" class="flex-1 bg-primary-700 text-primary-200 px-4 py-2.5 rounded-lg hover:bg-primary-600 transition font-medium text-sm">
                    <i class="fas fa-times mr-2"></i> Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<link href='https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css' rel='stylesheet' />
<script src='https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js'></script>

<style>
/* Hide Mapbox logo and attribution */
.mapboxgl-ctrl-logo,
.mapboxgl-ctrl-attrib,
.mapboxgl-compact {
    display: none !important;
}

/* Custom scrollbar for dark theme */
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: #0f172a;
    border-radius: 3px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #334155;
    border-radius: 3px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #475569;
}

/* Team marker labels */
.team-marker-label {
    background: #0A0A0A;
    color: #4ECDC4;
    padding: 4px 8px;
    border-radius: 4px;
    font-weight: bold;
    font-size: 12px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.5);
    white-space: nowrap;
    border: 1px solid #4ECDC4;
}

/* Selected team card styling */
.team-card-selected {
    border-color: #4ECDC4 !important;
    background-color: rgba(78, 205, 196, 0.1) !important;
    box-shadow: 0 0 0 2px rgba(78, 205, 196, 0.2);
}
</style>

<script>
mapboxgl.accessToken = '{{ env('MAPBOX_ACCESS_TOKEN') }}';

const teams = @json($teams);
const reportLat = {{ $report->latitude ?? 10.465 }};
const reportLng = {{ $report->longitude ?? 122.826 }};
const assignedTeamId = '{{ $report->assigned_team_id }}';

const map = new mapboxgl.Map({
    container: 'map',
    style: 'mapbox://styles/mapbox/streets-v12',
    center: [reportLng, reportLat],
    zoom: 12,
    attributionControl: false // Disable attribution control
});

// Add emergency location marker (blue)
new mapboxgl.Marker({ color: '#3B82F6' })
    .setLngLat([reportLng, reportLat])
    .setPopup(new mapboxgl.Popup().setHTML(`
        <div class="p-2">
            <h4 class="font-bold">Emergency Location</h4>
            <p class="text-sm">{{ $report->emergency_type }}</p>
        </div>
    `))
    .addTo(map);

// Add team markers (red) with labels
const teamMarkers = {};
teams.forEach(team => {
    if (team.latitude && team.longitude) {
        // Create custom marker element with label
        const el = document.createElement('div');
        el.style.cursor = 'pointer';
        el.innerHTML = `
            <div style="text-align: center;">
                <div class="team-marker-label" style="margin-bottom: 4px;">${team.team_name}</div>
                <div style="width: 30px; height: 30px; background-color: #EF4444; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.3);"></div>
            </div>
        `;
        
        el.addEventListener('click', () => {
            selectTeam(team.id);
        });

        const marker = new mapboxgl.Marker({ element: el })
            .setLngLat([parseFloat(team.longitude), parseFloat(team.latitude)])
            .addTo(map);
        
        teamMarkers[team.id] = marker;
    }
});

// Add navigation controls
map.addControl(new mapboxgl.NavigationControl());

// Fit map to show all markers
if (teams.length > 0) {
    const bounds = new mapboxgl.LngLatBounds();
    bounds.extend([reportLng, reportLat]);
    teams.forEach(team => {
        if (team.latitude && team.longitude) {
            bounds.extend([parseFloat(team.longitude), parseFloat(team.latitude)]);
        }
    });
    map.fitBounds(bounds, { padding: 50 });
}

// Highlight assigned team on load
if (assignedTeamId) {
    const assignedCard = document.getElementById('team-card-' + assignedTeamId);
    if (assignedCard) {
        assignedCard.classList.add('team-card-selected');
    }
}

let selectedTeamId = null;
let routeLayer = null;
let showRoutes = true;

function selectTeam(teamId) {
    // Remove previous selection
    document.querySelectorAll('[id^="team-card-"]').forEach(card => {
        card.classList.remove('team-card-selected');
    });
    
    // Add selection to clicked team
    const teamCard = document.getElementById('team-card-' + teamId);
    if (teamCard) {
        teamCard.classList.add('team-card-selected');
        teamCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
    
    selectedTeamId = teamId;
    
    // Draw route if toggle is on
    if (showRoutes) {
        drawRoute(teamId);
    }
}

function selectTeamFromList(teamId, lat, lng) {
    selectTeam(teamId);
    
    // Fly to team location
    map.flyTo({
        center: [lng, lat],
        zoom: 15,
        essential: true
    });
}

function toggleRoutes() {
    showRoutes = document.getElementById('showRoutesToggle').checked;
    
    if (showRoutes && selectedTeamId) {
        drawRoute(selectedTeamId);
    } else {
        clearRoute();
    }
}

function drawRoute(teamId) {
    const team = teams.find(t => t.id === teamId);
    if (!team) return;
    
    // Clear existing route
    clearRoute();
    
    // Fetch route from Mapbox Directions API
    const url = `https://api.mapbox.com/directions/v5/mapbox/driving/${team.longitude},${team.latitude};${reportLng},${reportLat}?geometries=geojson&access_token=${mapboxgl.accessToken}`;
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.routes && data.routes.length > 0) {
                const route = data.routes[0].geometry;
                
                // Add route source
                if (!map.getSource('route')) {
                    map.addSource('route', {
                        type: 'geojson',
                        data: {
                            type: 'Feature',
                            properties: {},
                            geometry: route
                        }
                    });
                    
                    // Add route layer
                    map.addLayer({
                        id: 'route',
                        type: 'line',
                        source: 'route',
                        layout: {
                            'line-join': 'round',
                            'line-cap': 'round'
                        },
                        paint: {
                            'line-color': '#3B82F6',
                            'line-width': 5,
                            'line-opacity': 0.75
                        }
                    });
                    
                    // Add route outline
                    map.addLayer({
                        id: 'route-outline',
                        type: 'line',
                        source: 'route',
                        layout: {
                            'line-join': 'round',
                            'line-cap': 'round'
                        },
                        paint: {
                            'line-color': '#1E40AF',
                            'line-width': 7,
                            'line-opacity': 0.4
                        }
                    }, 'route');
                } else {
                    map.getSource('route').setData({
                        type: 'Feature',
                        properties: {},
                        geometry: route
                    });
                }
                
                // Fit map to show the route
                const coordinates = route.coordinates;
                const bounds = coordinates.reduce((bounds, coord) => {
                    return bounds.extend(coord);
                }, new mapboxgl.LngLatBounds(coordinates[0], coordinates[0]));
                
                map.fitBounds(bounds, {
                    padding: 50
                });
            }
        })
        .catch(error => {
            console.error('Error fetching route:', error);
        });
}

function clearRoute() {
    if (map.getLayer('route')) {
        map.removeLayer('route');
    }
    if (map.getLayer('route-outline')) {
        map.removeLayer('route-outline');
    }
    if (map.getSource('route')) {
        map.removeSource('route');
    }
}

function showConfirmModal(teamId, teamName, memberCount, specialization, distance) {
    document.getElementById('selectedTeamId').value = teamId;
    document.getElementById('modalContent').innerHTML = `
        <div class="space-y-3">
            <p class="text-sm text-primary-300">You are about to assign:</p>
            <div class="bg-primary-700 p-4 rounded-lg border border-primary-600">
                <p class="font-bold text-base text-primary-100">${teamName}</p>
                <p class="text-xs text-primary-400 mt-1">${specialization}</p>
                <p class="text-xs mt-2 text-primary-300"><strong>Distance:</strong> <span class="text-secondary-400">${distance} km away</span></p>
                <p class="text-xs text-primary-300"><strong>${memberCount} team members</strong> will be notified</p>
            </div>
            <p class="text-xs text-primary-400">
                <i class="fas fa-bell mr-1"></i> All logged-in team members will receive push notifications.
            </p>
        </div>
    `;
    document.getElementById('confirmModal').classList.remove('hidden');
    document.getElementById('confirmModal').classList.add('flex');
}

function closeModal() {
    document.getElementById('confirmModal').classList.add('hidden');
    document.getElementById('confirmModal').classList.remove('flex');
}
</script>
@endsection
