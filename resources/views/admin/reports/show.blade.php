@extends('layouts.admin')

@section('title', 'View Report')
@section('header', 'Emergency Report Details')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Report Details -->
    <div class="lg:col-span-2 space-y-6">
        <div class="card border border-primary-700 p-6">
            <h3 class="text-lg font-semibold text-primary-100 mb-4">Report Information</h3>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-primary-400 text-xs block mb-1">Emergency Type</label>
                    <p class="font-semibold text-primary-100">{{ $report->emergency_type }}</p>
                </div>
                <div>
                    <label class="text-primary-400 text-xs block mb-1">Status</label>
                    <p>
                        @if($report->status === 'Pending')
                            <span class="px-3 py-1 text-xs rounded-full bg-accent-900 text-accent-300 font-medium">Pending</span>
                        @elseif($report->status === 'In Progress')
                            <span class="px-3 py-1 text-xs rounded-full bg-secondary-900 text-secondary-300 font-medium">In Progress</span>
                        @else
                            <span class="px-3 py-1 text-xs rounded-full bg-secondary-400 text-primary-950 font-medium">Completed</span>
                        @endif
                    </p>
                </div>
                <div class="col-span-2">
                    <label class="text-primary-400 text-xs block mb-1">Location</label>
                    <p class="font-semibold text-primary-100">{{ $report->location }}</p>
                </div>
                @if($report->latitude && $report->longitude)
                <div class="col-span-2">
                    <label class="text-primary-400 text-xs block mb-1">Coordinates</label>
                    <p class="font-semibold text-primary-200">{{ $report->latitude }}, {{ $report->longitude }}</p>
                </div>
                @endif
                <div class="col-span-2">
                    <label class="text-primary-400 text-xs block mb-1">Description</label>
                    <p class="text-primary-200">{{ $report->description }}</p>
                </div>
                <div>
                    <label class="text-primary-400 text-xs block mb-1">Reported At</label>
                    <p class="font-semibold text-primary-200">{{ $report->created_at->format('M d, Y H:i A') }}</p>
                </div>
                <div>
                    <label class="text-primary-400 text-xs block mb-1">Last Updated</label>
                    <p class="font-semibold text-primary-200">{{ $report->updated_at->format('M d, Y H:i A') }}</p>
                </div>
            </div>

            @if($report->photo_url)
            <div class="mt-4">
                <label class="text-primary-400 text-xs block mb-2">Photo</label>
                <img src="{{ $report->photo_url }}" alt="Report Photo" class="mt-2 rounded-lg max-w-md border border-primary-700">
            </div>
            @endif
        </div>

        <!-- Map View -->
        @if($report->latitude && $report->longitude)
        <div class="card border border-primary-700 p-6">
            <h3 class="text-lg font-semibold text-primary-100 mb-4">
                <i class="fas fa-map-location-dot text-secondary-400 mr-2"></i>Location Map
            </h3>
            <div id="map" style="height: 400px;" class="rounded-lg border border-primary-700 overflow-hidden"></div>
            <div class="mt-3 p-3 bg-secondary-900/20 border border-secondary-900 rounded text-xs text-secondary-300">
                <i class="fas fa-info-circle mr-1"></i> 
                <strong>Emergency Location:</strong> {{ $report->location }}
            </div>
        </div>
        @endif

        <!-- Messages -->
        @if($report->messages->count() > 0)
        <div class="card border border-primary-700 p-6">
            <h3 class="text-lg font-semibold text-primary-100 mb-4">
                <i class="fas fa-comments text-secondary-400 mr-2"></i>Messages ({{ $report->messages->count() }})
            </h3>
            <div class="space-y-4 max-h-96 overflow-y-auto custom-scrollbar">
                @foreach($report->messages as $message)
                <div class="border-l-4 border-secondary-400 pl-4 py-2 bg-primary-700 rounded-r">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="font-semibold text-sm text-primary-100">{{ $message->sender->full_name }}</p>
                            <p class="text-primary-200 text-sm">{{ $message->message }}</p>
                        </div>
                        <span class="text-xs text-primary-500">{{ $message->created_at->format('M d, H:i') }}</span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Citizen Info -->
        <div class="card border border-primary-700 p-6">
            <h3 class="text-base font-semibold text-primary-100 mb-4">
                <i class="fas fa-user text-secondary-400 mr-2"></i>Citizen Information
            </h3>
            @if($report->citizen)
                @if(!$report->show_name)
                    <div class="mb-3 p-3 bg-accent-900/20 border-l-4 border-accent-400 rounded">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-user-shield text-accent-400"></i>
                            <p class="text-xs text-accent-300 font-semibold">
                                Privacy: Identity hidden from rescuers
                            </p>
                        </div>
                    </div>
                @endif
                <div class="space-y-2 text-sm">
                    <p><span class="text-primary-400">Name:</span> <span class="font-semibold text-primary-100">{{ $report->citizen->full_name }}</span></p>
                    <p><span class="text-primary-400">Email:</span> <span class="font-semibold text-primary-200">{{ $report->citizen->email }}</span></p>
                    @if($report->citizen->phone)
                    <p><span class="text-primary-400">Phone:</span> <span class="font-semibold text-primary-200">{{ $report->citizen->phone }}</span></p>
                    @endif
                </div>
            @else
                <div class="p-4 bg-primary-700 border border-primary-600 rounded">
                    <p class="text-primary-300 text-xs">
                        <i class="fas fa-info-circle text-primary-500 mr-1"></i> 
                        This is a guest report. No registered citizen account.
                    </p>
                </div>
            @endif
        </div>

        <!-- Rescuer/Team Info -->
        <div class="card border border-primary-700 p-6">
            <h3 class="text-base font-semibold text-primary-100 mb-4">
                <i class="fas fa-user-shield text-secondary-400 mr-2"></i>Assignment
            </h3>
            
            @if($report->assignedRescuer)
                <div class="mb-4">
                    <label class="text-primary-400 text-xs font-semibold block mb-2">Assigned Rescuer</label>
                    <div class="space-y-2 text-sm">
                        <p><span class="text-primary-400">Name:</span> <span class="font-semibold text-primary-100">{{ $report->assignedRescuer->full_name }}</span></p>
                        <p><span class="text-primary-400">Email:</span> <span class="font-semibold text-primary-200">{{ $report->assignedRescuer->email }}</span></p>
                        @if($report->assignedRescuer->phone)
                        <p><span class="text-primary-400">Phone:</span> <span class="font-semibold text-primary-200">{{ $report->assignedRescuer->phone }}</span></p>
                        @endif
                    </div>
                </div>
            @elseif($report->assignedTeam)
                <div class="mb-4">
                    <label class="text-primary-400 text-xs font-semibold block mb-2">Assigned Team</label>
                    <div class="p-3 bg-secondary-900/20 border border-secondary-900 rounded">
                        <p class="font-semibold text-secondary-300">
                            <i class="fas fa-users mr-1"></i>{{ $report->assignedTeam->team_name }}
                        </p>
                        <p class="text-xs text-primary-300 mt-1">{{ $report->assignedTeam->specialization }}</p>
                        @if($report->assignedTeam->contact_number)
                        <p class="text-xs text-primary-300 mt-1">
                            <i class="fas fa-phone mr-1"></i>{{ $report->assignedTeam->contact_number }}
                        </p>
                        @endif
                    </div>
                </div>
            @else
                <p class="text-primary-500 italic text-sm">No rescuer or team assigned yet</p>
            @endif
        </div>

        <!-- Actions -->
        <div class="card border border-primary-700 p-6">
            <h3 class="text-base font-semibold text-primary-100 mb-4">
                <i class="fas fa-bolt text-secondary-400 mr-2"></i>Actions
            </h3>
            <div class="space-y-2">
                @if($report->status === 'Completed')
                    <button disabled class="block w-full bg-primary-600 text-primary-400 text-center px-4 py-2.5 rounded-lg cursor-not-allowed text-sm font-medium">
                        <i class="fas fa-check-circle mr-2"></i> Report Completed
                    </button>
                    <p class="text-xs text-primary-500 text-center mt-2">
                        <i class="fas fa-info-circle mr-1"></i> Cannot assign teams to completed reports
                    </p>
                @else
                    <a href="{{ route('admin.reports.assign-map', $report) }}" class="block w-full bg-secondary-400 text-primary-950 text-center px-4 py-2.5 rounded-lg hover:bg-secondary-500 transition text-sm font-medium">
                        <i class="fas fa-map-marked-alt mr-2"></i> Assign Rescue Team
                    </a>
                @endif
                <a href="{{ route('admin.reports.index') }}" class="block w-full bg-primary-700 text-primary-200 text-center px-4 py-2.5 rounded-lg hover:bg-primary-600 transition text-sm font-medium">
                    <i class="fas fa-arrow-left mr-2"></i> Back to List
                </a>
            </div>
        </div>
    </div>
</div>

@if($report->latitude && $report->longitude)
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
</style>

<script>
mapboxgl.accessToken = '{{ env('MAPBOX_ACCESS_TOKEN') }}';

const reportLat = {{ $report->latitude }};
const reportLng = {{ $report->longitude }};

const map = new mapboxgl.Map({
    container: 'map',
    style: 'mapbox://styles/mapbox/streets-v12',
    center: [reportLng, reportLat],
    zoom: 15,
    attributionControl: false
});

// Get icon based on emergency type
function getEmergencyIcon(type) {
    const icons = {
        'Fire': 'fa-fire',
        'Flood': 'fa-water',
        'Earthquake': 'fa-house-damage',
        'Medical Emergency': 'fa-briefcase-medical',
        'Accident': 'fa-car-crash',
        'Other': 'fa-exclamation-triangle'
    };
    return icons[type] || 'fa-exclamation-circle';
}

// Get color based on emergency type
function getEmergencyColor(type) {
    const colors = {
        'Fire': '#EF4444',           // Red
        'Flood': '#3B82F6',          // Blue
        'Earthquake': '#F59E0B',     // Amber
        'Medical Emergency': '#EC4899', // Pink
        'Accident': '#8B5CF6',       // Purple
        'Other': '#6B7280'           // Gray
    };
    return colors[type] || '#EF4444';
}

const emergencyType = '{{ $report->emergency_type }}';
const emergencyIcon = getEmergencyIcon(emergencyType);
const emergencyColor = getEmergencyColor(emergencyType);

// Add emergency location marker with dynamic icon
const el = document.createElement('div');
el.innerHTML = `
    <div style="text-align: center;">
        <div style="background: ${emergencyColor}; width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 4px solid white; box-shadow: 0 4px 8px rgba(0,0,0,0.3);">
            <i class="fas ${emergencyIcon}" style="color: white; font-size: 24px;"></i>
        </div>
    </div>
`;
el.style.cursor = 'pointer';

new mapboxgl.Marker({ element: el })
    .setLngLat([reportLng, reportLat])
    .setPopup(new mapboxgl.Popup({ offset: 25 }).setHTML(`
        <div class="p-3">
            <h4 class="font-bold text-red-600 mb-2">🚨 Emergency Location</h4>
            <p class="text-sm"><strong>Type:</strong> {{ $report->emergency_type }}</p>
            <p class="text-sm"><strong>Location:</strong> {{ $report->location }}</p>
            <p class="text-sm text-gray-600 mt-2">{{ $report->created_at->format('M d, Y H:i A') }}</p>
        </div>
    `))
    .addTo(map);

// Add navigation controls
map.addControl(new mapboxgl.NavigationControl());

// Open popup by default
setTimeout(() => {
    const popup = document.querySelector('.mapboxgl-popup');
    if (!popup) {
        map.flyTo({ center: [reportLng, reportLat], zoom: 15 });
    }
}, 500);
</script>
@endif

@endsection
