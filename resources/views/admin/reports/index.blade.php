@extends('layouts.admin')

@section('title', 'Emergency Reports')
@section('header', 'Emergency Reports')
@section('subtitle', 'Manage and assign emergency incidents')

@section('content')
<!-- Filters -->
<div class="card p-5 mb-6 border border-primary-700">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-sm font-semibold text-primary-100">Filters</h3>
        @if(request()->hasAny(['status', 'emergency_type', 'search']))
            <a href="{{ route('admin.reports.index') }}" class="text-xs text-secondary-400 hover:text-secondary-300">
                <i class="fas fa-times-circle mr-1"></i>Clear all
            </a>
        @endif
    </div>
    <form method="GET" id="filter-form" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="block text-xs text-primary-400 mb-2">Status</label>
            <select name="status" class="input-field text-sm w-full" onchange="this.form.submit()">
                <option value="">All Status</option>
                <option value="Pending" {{ request('status') === 'Pending' ? 'selected' : '' }}>Pending</option>
                <option value="In Progress" {{ request('status') === 'In Progress' ? 'selected' : '' }}>In Progress</option>
                <option value="Completed" {{ request('status') === 'Completed' ? 'selected' : '' }}>Completed</option>
            </select>
        </div>
        
        <div>
            <label class="block text-xs text-primary-400 mb-2">Emergency Type</label>
            <select name="emergency_type" class="input-field text-sm w-full" onchange="this.form.submit()">
                <option value="">All Types</option>
                <option value="Fire" {{ request('emergency_type') === 'Fire' ? 'selected' : '' }}>Fire</option>
                <option value="Flood" {{ request('emergency_type') === 'Flood' ? 'selected' : '' }}>Flood</option>
                <option value="Earthquake" {{ request('emergency_type') === 'Earthquake' ? 'selected' : '' }}>Earthquake</option>
                <option value="Medical Emergency" {{ request('emergency_type') === 'Medical Emergency' ? 'selected' : '' }}>Medical Emergency</option>
                <option value="Accident" {{ request('emergency_type') === 'Accident' ? 'selected' : '' }}>Accident</option>
                <option value="Other" {{ request('emergency_type') === 'Other' ? 'selected' : '' }}>Other</option>
            </select>
        </div>
        
        <div>
            <label class="block text-xs text-primary-400 mb-2">Search</label>
            <div class="relative">
                <input type="text" name="search" id="search-input" placeholder="Search location..." class="input-field text-sm w-full pl-10" value="{{ request('search') }}">
                <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-primary-500 text-xs"></i>
            </div>
        </div>
    </form>
</div>

<!-- Reports Table -->
<div class="card border border-primary-700">
    <div class="overflow-x-auto">
        <table class="w-full" id="reports-table">
            <thead class="bg-primary-900 border-b border-primary-700">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-medium text-primary-400 uppercase tracking-wider">Type</th>
                    <th class="px-5 py-3 text-left text-xs font-medium text-primary-400 uppercase tracking-wider">Location</th>
                    <th class="px-5 py-3 text-left text-xs font-medium text-primary-400 uppercase tracking-wider">Citizen</th>
                    <th class="px-5 py-3 text-left text-xs font-medium text-primary-400 uppercase tracking-wider">Assigned</th>
                    <th class="px-5 py-3 text-left text-xs font-medium text-primary-400 uppercase tracking-wider">Status</th>
                    <th class="px-5 py-3 text-left text-xs font-medium text-primary-400 uppercase tracking-wider">Date</th>
                    <th class="px-5 py-3 text-left text-xs font-medium text-primary-400 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-primary-700">
                @forelse($reports as $report)
                <tr data-report-id="{{ $report->id }}" class="hover:bg-primary-700 transition">
                    <td class="px-5 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs rounded-md bg-accent-900 text-accent-300 font-medium">
                            {{ $report->emergency_type }}
                        </span>
                    </td>
                    <td class="px-5 py-4">
                        <span class="text-sm text-primary-200">
                            @php
                                $locationParts = explode(':', $report->location);
                                $reversedLocation = array_reverse($locationParts);
                                $formattedLocation = implode(': ', $reversedLocation);
                            @endphp
                            {{ Str::limit($formattedLocation, 40) }}
                        </span>
                    </td>
                    <td class="px-5 py-4">
                        <span class="text-sm text-primary-200">{{ $report->citizen->full_name ?? 'N/A' }}</span>
                    </td>
                    <td class="px-5 py-4">
                        @if($report->assignedTeam)
                            <span class="inline-flex items-center text-sm text-secondary-400">
                                <i class="fas fa-users mr-1 text-xs"></i>
                                {{ $report->assignedTeam->team_name }}
                            </span>
                        @elseif($report->assignedRescuer)
                            <span class="inline-flex items-center text-sm text-secondary-400">
                                <i class="fas fa-user mr-1 text-xs"></i>
                                {{ $report->assignedRescuer->full_name }}
                            </span>
                        @else
                            <span class="text-sm text-primary-500">Unassigned</span>
                        @endif
                    </td>
                    <td class="px-5 py-4">
                        <span class="status-badge" data-status="{{ $report->status }}">
                            @if($report->status === 'Pending')
                                <span class="px-2 py-1 text-xs rounded-md bg-accent-900 text-accent-300 font-medium">Pending</span>
                            @elseif($report->status === 'In Progress')
                                <span class="px-2 py-1 text-xs rounded-md bg-secondary-900 text-secondary-300 font-medium">In Progress</span>
                            @else
                                <span class="px-2 py-1 text-xs rounded-md bg-primary-700 text-primary-300 font-medium">Completed</span>
                            @endif
                        </span>
                    </td>
                    <td class="px-5 py-4">
                        <span class="text-xs text-primary-400">{{ $report->created_at ? $report->created_at->format('M d, Y H:i') : 'N/A' }}</span>
                    </td>
                    <td class="px-5 py-4 whitespace-nowrap">
                        <div class="flex items-center space-x-2">
                            @if($report->status !== 'Completed')
                                <a href="{{ route('admin.reports.assign-map', $report) }}" 
                                   class="inline-flex items-center px-3 py-1.5 bg-secondary-400 hover:bg-secondary-500 text-primary-950 rounded-lg transition font-medium text-xs"
                                   title="Assign Team">
                                    <i class="fas fa-users mr-1.5"></i>
                                    Assign Team
                                </a>
                            @else
                                <span class="inline-flex items-center px-3 py-1.5 bg-primary-700 text-primary-500 rounded-lg text-xs cursor-not-allowed">
                                    <i class="fas fa-check-circle mr-1.5"></i>
                                    Completed
                                </span>
                            @endif
                            <a href="{{ route('admin.reports.show', $report) }}" 
                               class="text-secondary-400 hover:text-secondary-300 transition p-1.5" 
                               title="View Details">
                                <i class="fas fa-eye text-sm"></i>
                            </a>
                            <form action="{{ route('admin.reports.destroy', $report) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this report?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-accent-400 hover:text-accent-300 transition p-1.5" title="Delete">
                                    <i class="fas fa-trash text-sm"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr id="no-reports-row">
                    <td colspan="7" class="px-5 py-8 text-center">
                        <i class="fas fa-inbox text-3xl text-primary-700 mb-2"></i>
                        <p class="text-sm text-primary-500">No reports found</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($reports->hasPages())
    <div class="p-5 border-t border-primary-700">
        <div class="flex items-center justify-between">
            <div class="text-sm text-primary-400">
                Showing {{ $reports->firstItem() }} to {{ $reports->lastItem() }} of {{ $reports->total() }} results
            </div>
            <div class="flex space-x-2">
                @if($reports->onFirstPage())
                    <span class="px-3 py-1.5 text-sm bg-primary-700 text-primary-500 rounded cursor-not-allowed">Previous</span>
                @else
                    <a href="{{ $reports->appends(request()->query())->previousPageUrl() }}" class="px-3 py-1.5 text-sm bg-primary-700 hover:bg-primary-600 text-primary-200 rounded transition">Previous</a>
                @endif

                <span class="px-3 py-1.5 text-sm bg-primary-700 text-primary-300">
                    Page {{ $reports->currentPage() }} of {{ $reports->lastPage() }}
                </span>

                @if($reports->hasMorePages())
                    <a href="{{ $reports->appends(request()->query())->nextPageUrl() }}" class="px-3 py-1.5 text-sm bg-secondary-400 hover:bg-secondary-500 text-primary-950 rounded transition font-medium">Next</a>
                @else
                    <span class="px-3 py-1.5 text-sm bg-primary-700 text-primary-500 rounded cursor-not-allowed">Next</span>
                @endif
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
// Debounced search
let searchTimeout;
const searchInput = document.getElementById('search-input');
const filterForm = document.getElementById('filter-form');

searchInput?.addEventListener('input', function(e) {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        filterForm.submit();
    }, 500); // 500ms debounce
});

// Wait for Supabase client to be initialized
function waitForSupabase(callback, maxAttempts = 10) {
    let attempts = 0;
    const checkInterval = setInterval(() => {
        attempts++;
        if (window.supabaseClient) {
            clearInterval(checkInterval);
            console.log('✅ Supabase client ready for reports page');
            callback();
        } else if (attempts >= maxAttempts) {
            clearInterval(checkInterval);
            console.error('❌ Supabase client not available after', maxAttempts, 'attempts');
        } else {
            console.log('⏳ Waiting for Supabase client... attempt', attempts);
        }
    }, 500);
}

// Real-time subscriptions for reports
function setupRealtimeSubscriptions() {
    console.log('📡 Setting up real-time subscription for reports page...');
    
    const reportsChannel = window.supabaseClient
        .channel('reports_page_realtime')
        .on('postgres_changes', 
            { event: '*', schema: 'public', table: 'emergency_reports' },
            async (payload) => {
                console.log('📡 Report change detected:', payload.eventType, payload);
                
                if (payload.eventType === 'INSERT') {
                    // New report added - reload page to show it
                    console.log('🆕 New report added, reloading...');
                    window.location.reload();
                    
                } else if (payload.eventType === 'UPDATE') {
                    // Report updated - update the row
                    const updatedReport = payload.new;
                    console.log('📝 Report updated:', updatedReport.id);
                    console.log('📝 New status:', updatedReport.status);
                    
                    const row = document.querySelector(`tr[data-report-id="${updatedReport.id}"]`);
                    if (row) {
                        console.log('✅ Found row to update');
                        
                        // Highlight the row
                        row.style.backgroundColor = 'rgba(16, 185, 129, 0.1)';
                        row.style.transition = 'background-color 0.3s';
                        setTimeout(() => {
                            row.style.backgroundColor = '';
                        }, 2000);
                        
                        // Update status badge with correct colors matching the blade template
                        const statusCell = row.querySelector('td:nth-child(5)');
                        if (statusCell) {
                            let statusClass = '';
                            let statusText = updatedReport.status;
                            
                            if (updatedReport.status === 'Pending') {
                                statusClass = 'bg-accent-900 text-accent-300';
                            } else if (updatedReport.status === 'In Progress') {
                                statusClass = 'bg-secondary-900 text-secondary-300';
                            } else if (updatedReport.status === 'Completed' || updatedReport.status === 'Resolved') {
                                statusClass = 'bg-primary-700 text-primary-300';
                                statusText = 'Completed';
                            } else if (updatedReport.status === 'Cancelled') {
                                statusClass = 'bg-red-900 text-red-300';
                            } else {
                                // Default fallback
                                statusClass = 'bg-primary-700 text-primary-300';
                            }
                            
                            statusCell.innerHTML = `
                                <span class="status-badge" data-status="${updatedReport.status}">
                                    <span class="px-2 py-1 text-xs rounded-md ${statusClass} font-medium">
                                        ${statusText}
                                    </span>
                                </span>
                            `;
                            console.log('✅ Status badge updated to:', statusText, 'with class:', statusClass);
                        } else {
                            console.error('❌ Status cell not found');
                        }
                        
                        console.log('✅ Row updated in UI');
                    } else {
                        console.log('⚠️ Row not found in table, might be filtered out');
                    }
                    
                } else if (payload.eventType === 'DELETE') {
                    // Report deleted - remove the row
                    const row = document.querySelector(`tr[data-report-id="${payload.old.id}"]`);
                    if (row) {
                        row.style.opacity = '0';
                        row.style.transition = 'opacity 0.3s';
                        setTimeout(() => row.remove(), 300);
                        console.log('🗑️ Row removed from UI');
                    }
                }
            }
        )
        .subscribe((status) => {
            console.log('📊 Reports page subscription status:', status);
            if (status === 'SUBSCRIBED') {
                console.log('✅ Successfully subscribed to emergency_reports changes');
            } else if (status === 'CLOSED') {
                console.log('⚠️ Subscription closed');
            } else if (status === 'CHANNEL_ERROR') {
                console.error('❌ Subscription error');
            }
        });
    
    // Cleanup on page unload
    window.addEventListener('beforeunload', () => {
        if (reportsChannel) {
            console.log('🧹 Cleaning up reports channel');
            window.supabaseClient.removeChannel(reportsChannel);
        }
    });
}

// Wait for Supabase to be ready, then setup subscriptions
waitForSupabase(setupRealtimeSubscriptions);
</script>
@endpush
