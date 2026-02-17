@extends('layouts.admin')

@section('title', 'Rescue Teams')
@section('header', 'Rescue Teams')
@section('subtitle', 'Manage rescue team operations')

@section('content')
<!-- Success Message -->
@if(session('success'))
<div class="card p-4 mb-6 border border-secondary-900 bg-secondary-900 bg-opacity-20">
    <div class="flex items-center">
        <i class="fas fa-check-circle text-secondary-400 mr-3"></i>
        <p class="text-sm text-secondary-300">{{ session('success') }}</p>
    </div>
</div>
@endif

<!-- Filters -->
<div class="card p-5 mb-6 border border-primary-700">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-sm font-semibold text-primary-100">Filters & Actions</h3>
        @if(request()->hasAny(['status', 'search']))
            <a href="{{ route('admin.teams.index') }}" class="text-xs text-secondary-400 hover:text-secondary-300">
                <i class="fas fa-times-circle mr-1"></i>Clear filters
            </a>
        @endif
    </div>
    <form method="GET" id="filter-form" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="block text-xs text-primary-400 mb-2">Status</label>
            <select name="status" class="input-field text-sm w-full" onchange="this.form.submit()">
                <option value="">All Status</option>
                <option value="Available" {{ request('status') === 'Available' ? 'selected' : '' }}>Available</option>
                <option value="On Mission" {{ request('status') === 'On Mission' ? 'selected' : '' }}>On Mission</option>
                <option value="Off Duty" {{ request('status') === 'Off Duty' ? 'selected' : '' }}>Off Duty</option>
            </select>
        </div>
        
        <div>
            <label class="block text-xs text-primary-400 mb-2">Search</label>
            <div class="relative">
                <input type="text" name="search" id="search-input" placeholder="Search teams..." class="input-field text-sm w-full pl-10" value="{{ request('search') }}">
                <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-primary-500 text-xs"></i>
            </div>
        </div>
        
        <div>
            <label class="block text-xs text-primary-400 mb-2">Actions</label>
            <a href="{{ route('admin.teams.create') }}" class="block text-center bg-secondary-400 hover:bg-secondary-500 text-primary-950 px-4 py-2 rounded-lg transition text-sm font-medium">
                <i class="fas fa-plus mr-1 text-xs"></i>Create Team
            </a>
        </div>
    </form>
</div>

<!-- Teams List -->
<div class="space-y-4">
    @forelse($teams as $team)
    <div class="card border border-primary-700 hover:border-secondary-400 transition">
        <!-- Team Header -->
        <div class="p-5 flex items-center justify-between cursor-pointer" onclick="toggleTeamMembers('team-{{ $team->id }}')">
            <div class="flex items-center flex-1">
                <div class="w-12 h-12 bg-primary-700 rounded-lg flex items-center justify-center mr-4">
                    <i class="fas fa-users text-secondary-400 text-lg"></i>
                </div>
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-1">
                        <h3 class="text-base font-semibold text-primary-100">{{ $team->team_name }}</h3>
                        @if($team->status === 'Available')
                            <span class="px-2 py-1 text-xs rounded-md bg-secondary-900 text-secondary-300 font-medium">Available</span>
                        @elseif($team->status === 'On Mission')
                            <span class="px-2 py-1 text-xs rounded-md bg-accent-900 text-accent-300 font-medium">On Mission</span>
                        @else
                            <span class="px-2 py-1 text-xs rounded-md bg-primary-700 text-primary-400 font-medium">Off Duty</span>
                        @endif
                    </div>
                    <div class="flex items-center gap-4 text-xs text-primary-400">
                        <span><i class="fas fa-briefcase mr-1"></i>{{ $team->specialization }}</span>
                        <span><i class="fas fa-users mr-1"></i>{{ $team->members_count }} Members</span>
                        <span><i class="fas fa-clock mr-1"></i>{{ $team->created_at->diffForHumans() }}</span>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.teams.show', $team) }}" class="bg-primary-700 hover:bg-primary-600 text-primary-200 px-4 py-2 rounded-lg transition text-xs" onclick="event.stopPropagation()">
                    <i class="fas fa-eye mr-1"></i>View Details
                </a>
                <a href="{{ route('admin.teams.edit', $team) }}" class="bg-secondary-900 hover:bg-secondary-800 text-secondary-300 px-4 py-2 rounded-lg transition text-xs" onclick="event.stopPropagation()">
                    <i class="fas fa-edit mr-1"></i>Edit
                </a>
                <button type="button" class="text-primary-400 hover:text-primary-200 px-2" id="expand-btn-{{ $team->id }}">
                    <i class="fas fa-chevron-down transition-transform"></i>
                </button>
            </div>
        </div>

        <!-- Team Members (Expandable) -->
        <div id="team-{{ $team->id }}" class="hidden border-t border-primary-700 bg-primary-900 bg-opacity-30">
            <div class="p-5">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="text-sm font-semibold text-primary-100">
                        <i class="fas fa-users text-secondary-400 mr-2"></i>Team Members ({{ $team->members->count() }})
                    </h4>
                </div>
                
                @if($team->members->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach($team->members as $member)
                    <div class="flex items-center p-3 bg-primary-800 rounded-lg border border-primary-700">
                        <div class="w-10 h-10 bg-primary-700 rounded-full flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-user text-secondary-400 text-sm"></i>
                        </div>
                        <div class="ml-3 flex-1 min-w-0">
                            <p class="text-sm font-medium text-primary-100 truncate">{{ $member->full_name }}</p>
                            <p class="text-xs text-primary-400 truncate">{{ $member->email }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="text-center py-6">
                    <i class="fas fa-user-slash text-2xl text-primary-700 mb-2"></i>
                    <p class="text-sm text-primary-500">No members assigned</p>
                </div>
                @endif
            </div>
        </div>
    </div>
    @empty
    <div class="card p-12 border border-primary-700 text-center">
        <i class="fas fa-users-slash text-4xl text-primary-700 mb-3"></i>
        <p class="text-sm text-primary-500">No rescue teams found</p>
        <a href="{{ route('admin.teams.create') }}" class="inline-block mt-4 bg-secondary-400 hover:bg-secondary-500 text-primary-950 px-6 py-2 rounded-lg transition text-sm font-medium">
            <i class="fas fa-plus mr-2"></i>Create First Team
        </a>
    </div>
    @endforelse
</div>

<!-- Pagination -->
@if($teams->hasPages())
<div class="mt-6 card p-5 border border-primary-700">
    <div class="flex items-center justify-between">
        <div class="text-sm text-primary-400">
            Showing {{ $teams->firstItem() }} to {{ $teams->lastItem() }} of {{ $teams->total() }} teams
        </div>
        <div class="flex space-x-2">
            @if($teams->onFirstPage())
                <span class="px-3 py-1.5 text-sm bg-primary-700 text-primary-500 rounded cursor-not-allowed">Previous</span>
            @else
                <a href="{{ $teams->appends(request()->query())->previousPageUrl() }}" class="px-3 py-1.5 text-sm bg-primary-700 hover:bg-primary-600 text-primary-200 rounded transition">Previous</a>
            @endif

            <span class="px-3 py-1.5 text-sm bg-primary-700 text-primary-300">
                Page {{ $teams->currentPage() }} of {{ $teams->lastPage() }}
            </span>

            @if($teams->hasMorePages())
                <a href="{{ $teams->appends(request()->query())->nextPageUrl() }}" class="px-3 py-1.5 text-sm bg-secondary-400 hover:bg-secondary-500 text-primary-950 rounded transition font-medium">Next</a>
            @else
                <span class="px-3 py-1.5 text-sm bg-primary-700 text-primary-500 rounded cursor-not-allowed">Next</span>
            @endif
        </div>
    </div>
</div>
@endif
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
    }, 500);
});

// Toggle team members visibility
function toggleTeamMembers(teamId) {
    const membersDiv = document.getElementById(teamId);
    const expandBtn = document.getElementById('expand-btn-' + teamId.replace('team-', ''));
    const icon = expandBtn.querySelector('i');
    
    if (membersDiv.classList.contains('hidden')) {
        membersDiv.classList.remove('hidden');
        icon.style.transform = 'rotate(180deg)';
    } else {
        membersDiv.classList.add('hidden');
        icon.style.transform = 'rotate(0deg)';
    }
}
</script>
@endpush
