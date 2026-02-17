@extends('layouts.admin')

@section('title', 'Dashboard - MDRRMO Admin')
@section('header', 'Dashboard')
@section('subtitle', 'Real-time emergency analytics and monitoring')

@section('content')
<!-- Key Metrics -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <!-- Total Reports -->
    <div class="card p-5 border border-primary-700">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 bg-primary-700 rounded-lg flex items-center justify-center">
                <i class="fas fa-file-lines text-primary-300 text-lg"></i>
            </div>
            <span class="text-xs text-primary-400 font-medium">ALL TIME</span>
        </div>
        <p class="text-xs text-primary-400 mb-1">Total Reports</p>
        <p id="stat-total-reports" class="text-2xl font-bold text-primary-100 transition-all duration-300">{{ $stats['total_reports'] }}</p>
    </div>

    <!-- Pending Reports -->
    <div class="card p-5 border border-accent-900">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 bg-accent-900 rounded-lg flex items-center justify-center">
                <i class="fas fa-clock text-accent-400 text-lg"></i>
            </div>
            <span class="text-xs text-accent-400 font-medium">URGENT</span>
        </div>
        <p class="text-xs text-primary-400 mb-1">Pending</p>
        <p id="stat-pending-reports" class="text-2xl font-bold text-accent-400 transition-all duration-300">{{ $stats['pending_reports'] }}</p>
    </div>

    <!-- In Progress -->
    <div class="card p-5 border border-secondary-900">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 bg-secondary-900 rounded-lg flex items-center justify-center">
                <i class="fas fa-truck-medical text-secondary-400 text-lg"></i>
            </div>
            <span class="text-xs text-secondary-400 font-medium">ACTIVE</span>
        </div>
        <p class="text-xs text-primary-400 mb-1">In Progress</p>
        <p id="stat-in-progress-reports" class="text-2xl font-bold text-secondary-400 transition-all duration-300">{{ $stats['in_progress_reports'] }}</p>
    </div>

    <!-- Completed -->
    <div class="card p-5 border border-primary-700">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 bg-primary-700 rounded-lg flex items-center justify-center">
                <i class="fas fa-circle-check text-secondary-400 text-lg"></i>
            </div>
            <span class="text-xs text-primary-400 font-medium">RESOLVED</span>
        </div>
        <p class="text-xs text-primary-400 mb-1">Completed</p>
        <p id="stat-completed-reports" class="text-2xl font-bold text-primary-100 transition-all duration-300">{{ $stats['completed_reports'] }}</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <!-- LEFT COLUMN (2/3 width) -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Urgent Emergency Reports (TOP LEFT) -->
        <div class="card p-4 border-2 border-dashed border-accent-400 bg-accent-900/5">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <i class="fas fa-exclamation-triangle text-accent-400 animate-pulse"></i>
                    <div>
                        <h3 class="text-sm font-semibold text-accent-300">Urgent Reports</h3>
                        <p class="text-xs text-primary-500">Pending assignments</p>
                    </div>
                </div>
                <a href="{{ route('admin.reports.index') }}" class="text-xs text-secondary-400 hover:text-secondary-300">
                    View All <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
            
            <div class="space-y-2 max-h-80 overflow-y-auto custom-scrollbar pr-1 urgent-reports-container">
                @php
                    $urgentReports = $recent_reports->where('status', 'Pending')->take(5);
                @endphp
                
                @forelse($urgentReports as $report)
                <div class="urgent-report-card bg-primary-800/50 border border-primary-700 rounded-lg p-3 hover:bg-primary-700/50 transition" data-report-id="{{ $report->id }}">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1.5">
                                <span class="px-2 py-0.5 text-xs rounded bg-accent-900 text-accent-300 font-medium">
                                    {{ $report->emergency_type }}
                                </span>
                                @if($report->status === 'Pending')
                                    <span class="px-2 py-0.5 text-xs rounded bg-accent-400 text-primary-950 font-bold">
                                        NEW
                                    </span>
                                @endif
                            </div>
                            <p class="text-xs text-primary-200 mb-1 truncate">
                                @php
                                    $locationParts = explode(':', $report->location);
                                    $reversedLocation = array_reverse($locationParts);
                                    $formattedLocation = implode(': ', $reversedLocation);
                                @endphp
                                {{ Str::limit($formattedLocation, 45) }}
                            </p>
                            <p class="text-xs text-primary-500">
                                {{ $report->citizen->full_name ?? 'Guest' }} • {{ $report->created_at ? $report->created_at->diffForHumans() : 'Just now' }}
                            </p>
                        </div>
                        <div class="flex gap-1.5 flex-shrink-0">
                            <a href="{{ route('admin.reports.assign-map', $report) }}" 
                               class="inline-flex items-center px-3 py-1.5 bg-secondary-400 hover:bg-secondary-500 text-primary-950 rounded-lg transition font-medium text-xs">
                                <i class="fas fa-users mr-1"></i>
                                Assign
                            </a>
                            <a href="{{ route('admin.reports.show', $report) }}" 
                               class="inline-flex items-center justify-center w-8 h-8 border border-primary-600 text-primary-300 hover:bg-primary-600 rounded-lg transition">
                                <i class="fas fa-eye text-xs"></i>
                            </a>
                        </div>
                    </div>
                </div>
                @empty
                <div class="text-center py-8">
                    <i class="fas fa-check-circle text-3xl text-secondary-400 mb-2"></i>
                    <p class="text-sm text-primary-400">No urgent reports</p>
                </div>
                @endforelse
            </div>
        </div>

        <!-- Bottom Row: Rescue Teams + User Stats (BOTTOM LEFT) -->
        <div class="grid grid-cols-2 gap-6">
            <!-- Rescue Teams -->
            <div class="card p-4 border border-primary-700">
                <div class="mb-3">
                    <h3 class="text-sm font-semibold text-primary-100">Rescue Teams</h3>
                    <p class="text-xs text-primary-500">Team availability</p>
                </div>
                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3 bg-secondary-900 bg-opacity-20 rounded-lg border border-secondary-900">
                        <div class="flex items-center space-x-2">
                            <i class="fas fa-circle-check text-secondary-400 text-sm"></i>
                            <span class="text-xs text-primary-200">Available</span>
                        </div>
                        <span id="stat-available-teams" class="text-xl font-bold text-secondary-400 transition-all duration-300">{{ $stats['available_teams'] }}</span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-primary-700 rounded-lg">
                        <div class="flex items-center space-x-2">
                            <i class="fas fa-truck-medical text-accent-400 text-sm"></i>
                            <span class="text-xs text-primary-200">On Mission</span>
                        </div>
                        <span class="text-xl font-bold text-accent-400 transition-all duration-300">{{ $stats['total_teams'] - $stats['available_teams'] }}</span>
                    </div>
                </div>
            </div>

            <!-- User Statistics -->
            <div class="card p-4 border border-primary-700">
                <div class="mb-3">
                    <h3 class="text-sm font-semibold text-primary-100">Users</h3>
                    <p class="text-xs text-primary-500">Platform statistics</p>
                </div>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <i class="fas fa-users text-primary-400 text-sm"></i>
                            <span class="text-xs text-primary-200">Citizens</span>
                        </div>
                        <span id="stat-total-citizens" class="text-sm font-semibold text-primary-100 transition-all duration-300">{{ $stats['total_citizens'] }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <i class="fas fa-user-shield text-secondary-400 text-sm"></i>
                            <span class="text-xs text-primary-200">Rescuers</span>
                        </div>
                        <span id="stat-total-rescuers" class="text-sm font-semibold text-secondary-400 transition-all duration-300">{{ $stats['total_rescuers'] }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <i class="fas fa-user-check text-secondary-400 text-sm"></i>
                            <span class="text-xs text-primary-200">Rescuers Ready</span>
                        </div>
                        <span id="stat-available-rescuers" class="text-sm font-semibold text-secondary-400 transition-all duration-300">{{ $stats['available_rescuers'] }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- RIGHT COLUMN (1/3 width) - Incident Heatmap -->
    <div class="card p-4 border border-primary-700">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-xs font-semibold text-primary-100">Incident Heatmap</h3>
            <select id="emergency-type-filter" class="px-2 py-1 text-xs bg-primary-700 text-primary-200 rounded border border-primary-600 focus:border-secondary-400 focus:outline-none">
                <option value="">All</option>
                <option value="Fire">Fire</option>
                <option value="Flood">Flood</option>
                <option value="Medical Emergency">Medical</option>
                <option value="Accident">Accident</option>
                <option value="Earthquake">Earthquake</option>
            </select>
        </div>
        <div id="incident-map" class="w-full h-64 bg-primary-900 rounded-lg border border-primary-700 relative overflow-hidden">
            <div class="absolute inset-0 flex items-center justify-center">
                <div class="text-center">
                    <i class="fas fa-map-location-dot text-3xl text-primary-700 mb-2"></i>
                    <p class="text-xs text-primary-500">Loading...</p>
                </div>
            </div>
        </div>
        
        <!-- Minimalist Legend -->
        <div class="grid grid-cols-2 gap-1.5 mt-3 text-xs">
            <div class="flex items-center gap-1.5">
                <div class="w-2 h-2 bg-accent-400 rounded-full"></div>
                <span class="text-primary-400">Fire</span>
            </div>
            <div class="flex items-center gap-1.5">
                <div class="w-2 h-2 bg-secondary-400 rounded-full"></div>
                <span class="text-primary-400">Flood</span>
            </div>
            <div class="flex items-center gap-1.5">
                <div class="w-2 h-2 bg-primary-300 rounded-full"></div>
                <span class="text-primary-400">Medical</span>
            </div>
            <div class="flex items-center gap-1.5">
                <div class="w-2 h-2 bg-accent-300 rounded-full"></div>
                <span class="text-primary-400">Other</span>
            </div>
        </div>
        
        <!-- Minimalist Breakdown -->
        <div class="mt-4 pt-4 border-t border-primary-700">
            <div class="space-y-2">
                @foreach($emergency_types->take(4) as $type)
                <div class="flex items-center justify-between text-xs">
                    <span class="text-primary-400 truncate">{{ Str::limit($type->emergency_type, 12) }}</span>
                    <span class="font-semibold text-primary-200">{{ $type->count }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<!-- Recent Reports -->
<div class="card border border-primary-700">
    <div class="p-5 border-b border-primary-700 flex items-center justify-between">
        <div>
            <h3 class="text-sm font-semibold text-primary-100">Recent Emergency Reports</h3>
            <p class="text-xs text-primary-500 mt-0.5">Latest incident reports from citizens</p>
        </div>
        <div id="new-reports-badge" class="hidden">
            <span class="px-3 py-1.5 bg-accent-400 text-primary-950 text-xs font-semibold rounded-lg animate-pulse">
                <i class="fas fa-bell mr-1"></i><span id="new-reports-count">0</span> New
            </span>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-primary-900 border-b border-primary-700">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-medium text-primary-400 uppercase tracking-wider">Type</th>
                    <th class="px-5 py-3 text-left text-xs font-medium text-primary-400 uppercase tracking-wider">Location</th>
                    <th class="px-5 py-3 text-left text-xs font-medium text-primary-400 uppercase tracking-wider">Citizen</th>
                    <th class="px-5 py-3 text-left text-xs font-medium text-primary-400 uppercase tracking-wider">Status</th>
                    <th class="px-5 py-3 text-left text-xs font-medium text-primary-400 uppercase tracking-wider">Date</th>
                    <th class="px-5 py-3 text-left text-xs font-medium text-primary-400 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody id="reports-tbody" class="divide-y divide-primary-700">
                @forelse($recent_reports as $index => $report)
                <tr data-report-id="{{ $report->id }}" class="hover:bg-primary-700 transition {{ $index === 0 ? 'bg-primary-700' : '' }}">
                    <td class="px-5 py-4 whitespace-nowrap">
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-1 text-xs rounded-md bg-accent-900 text-accent-300 font-medium">
                                {{ $report->emergency_type }}
                            </span>
                            @if($index === 0)
                                <span class="px-2 py-1 text-xs rounded-md bg-secondary-400 text-primary-950 font-bold">
                                    NEW
                                </span>
                            @endif
                        </div>
                    </td>
                    <td class="px-5 py-4">
                        <span class="text-sm text-primary-200">{{ Str::limit($report->location, 35) }}</span>
                    </td>
                    <td class="px-5 py-4">
                        <span class="text-sm text-primary-200">{{ $report->citizen->full_name ?? 'Guest' }}</span>
                    </td>
                    <td class="px-5 py-4">
                        @if($report->status === 'Pending')
                            <span class="px-2 py-1 text-xs rounded-md bg-accent-900 text-accent-300 font-medium">Pending</span>
                        @elseif($report->status === 'In Progress')
                            <span class="px-2 py-1 text-xs rounded-md bg-secondary-900 text-secondary-300 font-medium">In Progress</span>
                        @else
                            <span class="px-2 py-1 text-xs rounded-md bg-primary-700 text-primary-300 font-medium">Completed</span>
                        @endif
                    </td>
                    <td class="px-5 py-4">
                        <span class="text-xs text-primary-400">{{ $report->created_at ? $report->created_at->format('M d, Y H:i') : 'N/A' }}</span>
                    </td>
                    <td class="px-5 py-4">
                        <a href="{{ route('admin.reports.show', $report) }}" class="text-secondary-400 hover:text-secondary-300 transition">
                            <i class="fas fa-arrow-right text-sm"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr id="no-reports-row">
                    <td colspan="6" class="px-5 py-8 text-center">
                        <i class="fas fa-inbox text-3xl text-primary-700 mb-2"></i>
                        <p class="text-sm text-primary-500">No reports found</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script>
    let map;
    let markers = [];
    let allReports = []; // Store all reports for filtering
    
    // Initialize map
    function initMap() {
        // Default center (Philippines)
        const defaultCenter = [14.5995, 120.9842];
        
        map = L.map('incident-map', {
            zoomControl: true,
            attributionControl: false
        }).setView(defaultCenter, 12);

        // Dark theme tiles
        L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            maxZoom: 19
        }).addTo(map);

        // Load incident markers
        loadIncidentMarkers();
        
        // Add filter event listener
        const filterSelect = document.getElementById('emergency-type-filter');
        if (filterSelect) {
            filterSelect.addEventListener('change', function() {
                filterIncidentMarkers(this.value);
            });
        }
    }

    // Load incident markers from reports
    async function loadIncidentMarkers() {
        try {
            // Fetch reports with coordinates
            const { data: reports, error } = await window.supabaseClient
                .from('emergency_reports')
                .select('id, emergency_type, latitude, longitude, status')
                .not('latitude', 'is', null)
                .not('longitude', 'is', null)
                .limit(100);

            if (error) throw error;

            // Store all reports for filtering
            allReports = reports;
            
            // Display all markers initially
            displayMarkers(reports);
        } catch (error) {
            console.error('Error loading incident markers:', error);
        }
    }
    
    // Filter markers by emergency type
    function filterIncidentMarkers(emergencyType) {
        const filteredReports = emergencyType 
            ? allReports.filter(report => report.emergency_type === emergencyType)
            : allReports;
        
        displayMarkers(filteredReports);
    }
    
    // Display markers on map
    function displayMarkers(reports) {
        // Clear existing markers
        markers.forEach(marker => marker.remove());
        markers = [];

        // Add markers for each report
        reports.forEach(report => {
            const color = getIncidentColor(report.emergency_type);
            
            const marker = L.circleMarker([report.latitude, report.longitude], {
                radius: 8,
                fillColor: color,
                color: color,
                weight: 2,
                opacity: 0.8,
                fillOpacity: 0.6
            }).addTo(map);

            marker.bindPopup(`
                <div style="color: #0A0A0A; font-size: 12px;">
                    <strong>${report.emergency_type}</strong><br>
                    Status: ${report.status}
                </div>
            `);

            markers.push(marker);
        });

        // Fit bounds if we have markers
        if (markers.length > 0) {
            const group = L.featureGroup(markers);
            map.fitBounds(group.getBounds().pad(0.1));
        }
    }

    // Get color based on incident type
    function getIncidentColor(type) {
        const colors = {
            'Fire': '#FF6B6B',
            'Flood': '#4ECDC4',
            'Medical Emergency': '#cbd5e1',
            'Accident': '#fca5a5',
            'Earthquake': '#a16207',
            'Landslide': '#854d0e'
        };
        return colors[type] || '#fca5a5';
    }

    // Initialize map when page loads
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(() => {
            if (typeof L !== 'undefined') {
                initMap();
            }
        }, 500);
    });
    let rescuerProfileUpdateTimeout = null;
    let pendingReports = [];
    let newReportsCount = 0;

    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(() => {
            if (typeof window.supabaseClient === 'undefined') {
                console.error('❌ Supabase client not initialized');
                return;
            }

            console.log('🚀 Initializing real-time dashboard subscriptions...');

            // Subscribe to emergency reports
            const reportsChannel = window.supabaseClient
                .channel('dashboard_reports')
                .on('postgres_changes', 
                    { event: '*', schema: 'public', table: 'emergency_reports' },
                    async (payload) => {
                        console.log('📡 Report change:', payload);
                        
                        if (payload.eventType === 'INSERT') {
                            const newReport = payload.new;
                            console.log('🚨 New emergency report:', newReport);
                            
                            // Show notification
                            const msg = `🚨 New ${newReport.emergency_type} Report!`;
                            showNotification(msg, 'info');
                            showBrowserNotification('MDRRMO Alert', `New ${newReport.emergency_type} emergency at ${newReport.location}`);
                            
                            // Add to pending reports
                            pendingReports.push(newReport);
                            newReportsCount++;
                            updateNewReportsBadge();
                            
                            // Fetch full report data and add to table
                            await fetchAndAddReport(newReport.id);
                            
                            // Update all stats
                            await updateAllStats();
                            
                        } else if (payload.eventType === 'UPDATE') {
                            const updatedReport = payload.new;
                            const oldReport = payload.old;
                            console.log('📝 Report updated:', updatedReport);
                            
                            // Update the row in the Recent Reports table
                            updateReportRow(updatedReport);
                            
                            // Update the Urgent Reports section
                            updateUrgentReportsSection(updatedReport, oldReport);
                            
                            // Show notification
                            showNotification('📝 Report status updated', 'success');
                            
                            // Update stats if status changed
                            if (oldReport.status !== updatedReport.status) {
                                await updateAllStats();
                            }
                        } else if (payload.eventType === 'DELETE') {
                            console.log('🗑️ Report deleted:', payload.old);
                            
                            // Remove row from table
                            const row = document.querySelector(`tr[data-report-id="${payload.old.id}"]`);
                            if (row) {
                                row.style.opacity = '0';
                                setTimeout(() => row.remove(), 300);
                            }
                            
                            // Update stats
                            await updateAllStats();
                        }
                    }
                )
                .subscribe((status) => {
                    console.log('📊 Dashboard reports subscription:', status);
                    if (status === 'SUBSCRIBED') {
                        updateRealtimeStatus(true);
                    } else if (status === 'CLOSED' || status === 'CHANNEL_ERROR') {
                        updateRealtimeStatus(false);
                    }
                });

            channels.push(reportsChannel);

            // Subscribe to users
            const usersChannel = window.supabaseClient
                .channel('dashboard_users')
                .on('postgres_changes',
                    { event: '*', schema: 'public', table: 'users' },
                    async (payload) => {
                        console.log('👤 User change:', payload);
                        
                        if (payload.eventType === 'INSERT') {
                            showNotification('👤 New user registered', 'success');
                        } else if (payload.eventType === 'UPDATE') {
                            showNotification('👤 User updated', 'info');
                        } else if (payload.eventType === 'DELETE') {
                            showNotification('👤 User deleted', 'info');
                        }
                        
                        // Update user stats
                        await updateUserStats();
                    }
                )
                .subscribe((status) => {
                    console.log('👥 Users subscription:', status);
                });

            channels.push(usersChannel);

            // Subscribe to teams
            const teamsChannel = window.supabaseClient
                .channel('dashboard_teams')
                .on('postgres_changes',
                    { event: '*', schema: 'public', table: 'rescue_teams' },
                    async (payload) => {
                        console.log('🚒 Team change:', payload);
                        
                        if (payload.eventType === 'INSERT') {
                            showNotification('🚒 New team created', 'success');
                        } else if (payload.eventType === 'UPDATE') {
                            const oldStatus = payload.old.status;
                            const newStatus = payload.new.status;
                            
                            if (oldStatus !== newStatus) {
                                console.log(`🚒 Team status changed: ${oldStatus} → ${newStatus}`);
                                showNotification(`🚒 Team status: ${newStatus}`, 'info');
                                
                                // When team becomes Available, team members should be available too
                                if (newStatus === 'Available') {
                                    console.log('✅ Team is now Available - updating rescuer counts...');
                                    // Wait a bit for rescuer_profiles to be updated
                                    await new Promise(resolve => setTimeout(resolve, 1500));
                                }
                            }
                        } else if (payload.eventType === 'DELETE') {
                            showNotification('🚒 Team deleted', 'info');
                        }
                        
                        // Update team stats
                        await updateTeamStats();
                        
                        // IMPORTANT: Also update user stats when team status changes
                        // because team members' rescuer statuses should have changed too
                        console.log('🔄 Updating user stats after team change...');
                        await updateUserStats();
                        console.log('✅ User stats updated after team change');
                    }
                )
                .subscribe((status) => {
                    console.log('🚑 Teams subscription:', status);
                });

            channels.push(teamsChannel);

            // Subscribe to rescuer profiles (for status changes)
            const rescuerProfilesChannel = window.supabaseClient
                .channel('dashboard_rescuer_profiles')
                .on('postgres_changes',
                    { event: '*', schema: 'public', table: 'rescuer_profiles' },
                    async (payload) => {
                        console.log('👤 Rescuer profile change:', payload);
                        
                        if (payload.eventType === 'UPDATE') {
                            const oldStatus = payload.old.status;
                            const newStatus = payload.new.status;
                            
                            if (oldStatus !== newStatus) {
                                console.log(`👤 Rescuer status changed: ${oldStatus} → ${newStatus}`);
                            }
                        } else if (payload.eventType === 'INSERT') {
                            console.log('👤 New rescuer profile created');
                        }
                        
                        // Debounce: Clear previous timeout and set new one
                        // This ensures we only update once after all changes are done
                        if (rescuerProfileUpdateTimeout) {
                            clearTimeout(rescuerProfileUpdateTimeout);
                            console.log('⏱️ Cleared previous timeout');
                        }
                        
                        console.log('⏱️ Setting new timeout for user stats update...');
                        rescuerProfileUpdateTimeout = setTimeout(async () => {
                            console.log('🔄 Timeout fired! Updating user stats after rescuer profile changes...');
                            await updateUserStats();
                            rescuerProfileUpdateTimeout = null;
                            console.log('✅ User stats update completed');
                        }, 500); // Reduced from 1000ms to 500ms
                    }
                )
                .subscribe((status) => {
                    console.log('👤 Rescuer profiles subscription:', status);
                });

            channels.push(rescuerProfilesChannel);

            console.log('✅ All subscriptions initialized');
        }, 1000);
    });

    // Update new reports badge
    function updateNewReportsBadge() {
        const badge = document.getElementById('new-reports-badge');
        const countEl = document.getElementById('new-reports-count');
        
        if (newReportsCount > 0) {
            badge.classList.remove('hidden');
            countEl.textContent = newReportsCount;
        } else {
            badge.classList.add('hidden');
        }
    }

    // Fetch and add new report to table
    async function fetchAndAddReport(reportId) {
        try {
            console.log('📥 Fetching report:', reportId);
            
            // Fetch report data (without join to avoid RLS issues)
            const { data: report, error: reportError } = await window.supabaseClient
                .from('emergency_reports')
                .select('*')
                .eq('id', reportId)
                .single();

            if (reportError) {
                console.error('❌ Error fetching report:', reportError);
                throw reportError;
            }

            console.log('✅ Report fetched:', report);

            // If there's a citizen_id, try to fetch the citizen name
            if (report.citizen_id) {
                const { data: citizen, error: citizenError } = await window.supabaseClient
                    .from('users')
                    .select('full_name')
                    .eq('id', report.citizen_id)
                    .single();

                if (!citizenError && citizen) {
                    report.citizen = citizen;
                    console.log('✅ Citizen name fetched:', citizen.full_name);
                } else {
                    console.log('⚠️ Could not fetch citizen name, using Guest');
                    report.citizen = null;
                }
            } else {
                console.log('ℹ️ No citizen_id, this is a guest report');
                report.citizen = null;
            }

            // Add to table
            addReportToTable(report);
        } catch (error) {
            console.error('❌ Error in fetchAndAddReport:', error);
            // Even if fetch fails, still update stats
            console.log('⚠️ Could not add report to table, but stats will be updated');
        }
    }

    // Add report row to table
    function addReportToTable(report) {
        const tbody = document.getElementById('reports-tbody');
        const noReportsRow = document.getElementById('no-reports-row');
        
        // Remove "no reports" row if it exists
        if (noReportsRow) {
            noReportsRow.remove();
        }

        // Remove NEW badge and highlight from previous first row
        const previousFirstRow = tbody.querySelector('tr:first-child');
        if (previousFirstRow) {
            previousFirstRow.classList.remove('bg-primary-700');
            const newBadge = previousFirstRow.querySelector('.bg-secondary-400');
            if (newBadge && newBadge.textContent.includes('NEW')) {
                newBadge.remove();
            }
        }

        // Create new row
        const row = document.createElement('tr');
        row.setAttribute('data-report-id', report.id);
        row.className = 'new-item bg-primary-700 hover:bg-primary-700 transition';
        
        const citizenName = report.citizen?.full_name || 'Guest';
        
        // Use the same color classes as the blade template
        let statusClass = '';
        let statusText = report.status;
        if (report.status === 'Pending') {
            statusClass = 'bg-accent-900 text-accent-300';
        } else if (report.status === 'In Progress') {
            statusClass = 'bg-secondary-900 text-secondary-300';
        } else {
            statusClass = 'bg-primary-700 text-primary-300';
            statusText = 'Completed';
        }
        
        const date = new Date(report.created_at);
        const formattedDate = date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) + ' ' +
                             date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });

        row.innerHTML = `
            <td class="px-5 py-4 whitespace-nowrap">
                <div class="flex items-center gap-2">
                    <span class="px-2 py-1 text-xs rounded-md bg-accent-900 text-accent-300 font-medium">
                        ${report.emergency_type}
                    </span>
                    <span class="px-2 py-1 text-xs rounded-md bg-secondary-400 text-primary-950 font-bold">
                        NEW
                    </span>
                </div>
            </td>
            <td class="px-5 py-4">
                <span class="text-sm text-primary-200">${report.location.substring(0, 35)}${report.location.length > 35 ? '...' : ''}</span>
            </td>
            <td class="px-5 py-4">
                <span class="text-sm text-primary-200">${citizenName}</span>
            </td>
            <td class="px-5 py-4">
                <span class="px-2 py-1 text-xs rounded-md ${statusClass} font-medium">${statusText}</span>
            </td>
            <td class="px-5 py-4">
                <span class="text-xs text-primary-400">${formattedDate}</span>
            </td>
            <td class="px-5 py-4">
                <a href="/admin/reports/${report.id}" class="text-secondary-400 hover:text-secondary-300 transition">
                    <i class="fas fa-arrow-right text-sm"></i>
                </a>
            </td>
        `;

        // Insert at the top
        tbody.insertBefore(row, tbody.firstChild);

        // Highlight the new row with green flash
        setTimeout(() => {
            row.style.backgroundColor = 'rgba(16, 185, 129, 0.2)';
            setTimeout(() => {
                row.style.transition = 'background-color 2s';
                row.style.backgroundColor = ''; // Back to bg-primary-700
            }, 1000);
        }, 100);
    }

    // Update existing report row
    function updateReportRow(report) {
        const row = document.querySelector(`tr[data-report-id="${report.id}"]`);
        if (!row) {
            console.log('⚠️ Row not found for report:', report.id);
            return;
        }

        console.log('📝 Updating row for report:', report.id, 'Status:', report.status);

        // Use the same color classes as the blade template
        let statusClass = '';
        let statusText = report.status;
        
        if (report.status === 'Pending') {
            statusClass = 'bg-accent-900 text-accent-300';
        } else if (report.status === 'In Progress') {
            statusClass = 'bg-secondary-900 text-secondary-300';
        } else if (report.status === 'Completed' || report.status === 'Resolved') {
            statusClass = 'bg-primary-700 text-primary-300';
            statusText = 'Completed';
        } else {
            statusClass = 'bg-primary-700 text-primary-300';
        }

        const statusCell = row.querySelector('td:nth-child(4)');
        if (statusCell) {
            statusCell.innerHTML = `<span class="px-2 py-1 text-xs rounded-md ${statusClass} font-medium">${statusText}</span>`;
            
            // Flash the row with green highlight
            row.style.backgroundColor = 'rgba(16, 185, 129, 0.1)';
            row.style.transition = 'background-color 0.3s';
            setTimeout(() => {
                row.style.backgroundColor = '';
            }, 2000);
            
            console.log('✅ Row updated successfully');
        } else {
            console.error('❌ Status cell not found');
        }
    }

    // Update Urgent Reports section - Remove if status is not Pending
    function updateUrgentReportsSection(updatedReport, oldReport) {
        console.log('🚨 Checking urgent section for report:', updatedReport.id, 'Status:', updatedReport.status);
        
        // If status is not Pending, remove from urgent section
        if (updatedReport.status !== 'Pending') {
            const urgentCard = document.querySelector(`.urgent-report-card[data-report-id="${updatedReport.id}"]`);
            
            if (urgentCard) {
                console.log('🗑️ Removing from urgent section (status:', updatedReport.status + ')');
                urgentCard.style.opacity = '0';
                urgentCard.style.transition = 'opacity 0.3s';
                
                setTimeout(() => {
                    urgentCard.remove();
                    
                    // Check if urgent section is now empty
                    const urgentContainer = document.querySelector('.urgent-reports-container');
                    const remainingCards = urgentContainer?.querySelectorAll('.urgent-report-card').length || 0;
                    
                    if (urgentContainer && remainingCards === 0) {
                        urgentContainer.innerHTML = `
                            <div class="text-center py-8">
                                <i class="fas fa-check-circle text-3xl text-secondary-400 mb-2"></i>
                                <p class="text-sm text-primary-400">No urgent reports</p>
                            </div>
                        `;
                    }
                }, 300);
            }
        }
    }

    // Update all statistics
    async function updateAllStats() {
        console.log('📊 Updating all statistics...');
        await Promise.all([
            updateReportStats(),
            updateUserStats(),
            updateTeamStats(),
            updateEmergencyTypes()
        ]);
    }

    // Update report statistics using RPC function (bypasses RLS)
    async function updateReportStats() {
        try {
            // Call RPC function to get all report stats at once
            const { data, error } = await window.supabaseClient
                .rpc('get_report_stats');

            if (error) {
                console.error('❌ Error fetching report stats:', error);
                return;
            }

            if (data) {
                updateStatWithAnimation('stat-total-reports', data.total_reports);
                updateStatWithAnimation('stat-pending-reports', data.pending_reports);
                updateStatWithAnimation('stat-in-progress-reports', data.in_progress_reports);
                updateStatWithAnimation('stat-completed-reports', data.resolved_reports);
            }

            console.log('✅ Report stats updated');
        } catch (error) {
            console.error('❌ Error updating report stats:', error);
        }
    }

    // Update user statistics using RPC function (bypasses RLS)
    async function updateUserStats() {
        try {
            console.log('📊 Updating user statistics...');
            
            // Call RPC function to get all user stats at once
            const { data, error } = await window.supabaseClient
                .rpc('get_user_stats');

            if (error) {
                console.error('❌ Error fetching user stats:', error);
                return;
            }

            if (data) {
                console.log('👥 Total users:', data.total_users);
                console.log('👤 Citizens:', data.total_citizens);
                console.log('🚒 Rescuers:', data.total_rescuers);
                console.log('✅ Available rescuers:', data.available_rescuers);

                updateStatWithAnimation('stat-total-users', data.total_users);
                updateStatWithAnimation('stat-total-citizens', data.total_citizens);
                updateStatWithAnimation('stat-total-rescuers', data.total_rescuers);
                updateStatWithAnimation('stat-available-rescuers', data.available_rescuers);
            }

            console.log('✅ User stats updated');
        } catch (error) {
            console.error('❌ Error updating user stats:', error);
        }
    }

    // Update team statistics using RPC function (bypasses RLS)
    async function updateTeamStats() {
        try {
            // Call RPC function to get all team stats at once
            const { data, error } = await window.supabaseClient
                .rpc('get_team_stats');

            if (error) {
                console.error('❌ Error fetching team stats:', error);
                return;
            }

            if (data) {
                updateStatWithAnimation('stat-total-teams', data.total_teams);
                updateStatWithAnimation('stat-available-teams', data.available_teams);
            }

            console.log('✅ Team stats updated');
        } catch (error) {
            console.error('❌ Error updating team stats:', error);
        }
    }

    // Update emergency types using RPC function (bypasses RLS)
    async function updateEmergencyTypes() {
        try {
            const { data, error } = await window.supabaseClient
                .rpc('get_emergency_type_stats');

            if (error) throw error;

            // Update each type
            const listEl = document.getElementById('emergency-types-list');
            if (listEl && data) {
                listEl.innerHTML = '';
                Object.entries(data).forEach(([type, count]) => {
                    const div = document.createElement('div');
                    div.className = 'flex justify-between items-center';
                    div.setAttribute('data-type', type);
                    div.innerHTML = `
                        <span class="text-gray-600 text-sm">${type}</span>
                        <span class="bg-gray-200 px-2 py-1 rounded text-sm font-semibold">${count}</span>
                    `;
                    listEl.appendChild(div);
                });
            }

            console.log('✅ Emergency types updated');
        } catch (error) {
            console.error('❌ Error updating emergency types:', error);
        }
    }

    // Update stat with animation
    function updateStatWithAnimation(elementId, newValue) {
        const el = document.getElementById(elementId);
        if (!el) return;

        const oldValue = parseInt(el.textContent) || 0;
        
        if (oldValue !== newValue) {
            // Scale animation
            el.style.transform = 'scale(1.2)';
            el.style.color = '#10b981'; // Green flash
            
            setTimeout(() => {
                el.textContent = newValue;
                el.style.transition = 'all 0.3s';
                el.style.transform = 'scale(1)';
                
                setTimeout(() => {
                    el.style.color = '';
                }, 300);
            }, 150);
        }
    }
</script>
@endpush
