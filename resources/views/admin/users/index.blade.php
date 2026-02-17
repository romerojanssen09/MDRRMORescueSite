@extends('layouts.admin')

@section('title', 'Users')
@section('header', 'Users')
@section('subtitle', 'Manage system users and rescuers')

@section('content')
<!-- Filters -->
<div class="card p-5 mb-6 border border-primary-700">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-sm font-semibold text-primary-100">Filters & Actions</h3>
        @if(request()->hasAny(['role', 'search']))
            <a href="{{ route('admin.users.index') }}" class="text-xs text-secondary-400 hover:text-secondary-300">
                <i class="fas fa-times-circle mr-1"></i>Clear filters
            </a>
        @endif
    </div>
    <form method="GET" id="filter-form" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="block text-xs text-primary-400 mb-2">Role</label>
            <select name="role" class="input-field text-sm w-full" onchange="this.form.submit()">
                <option value="">All Roles</option>
                <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                <option value="rescuer" {{ request('role') === 'rescuer' ? 'selected' : '' }}>Rescuer</option>
                <option value="citizen" {{ request('role') === 'citizen' ? 'selected' : '' }}>Citizen</option>
            </select>
        </div>
        
        <div>
            <label class="block text-xs text-primary-400 mb-2">Search</label>
            <div class="relative">
                <input type="text" name="search" id="search-input" placeholder="Search users..." class="input-field text-sm w-full pl-10" value="{{ request('search') }}">
                <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-primary-500 text-xs"></i>
            </div>
        </div>
        
        <div>
            <label class="block text-xs text-primary-400 mb-2">Actions</label>
            <div class="flex gap-2">
                <a href="{{ route('admin.users.create') }}" class="flex-1 text-center bg-secondary-400 hover:bg-secondary-500 text-primary-950 px-4 py-2 rounded-lg transition text-sm font-medium">
                    <i class="fas fa-user-plus mr-1 text-xs"></i>Add Rescuer
                </a>
                <a href="{{ route('admin.users.sync') }}" class="bg-primary-700 hover:bg-primary-600 text-primary-200 px-4 py-2 rounded-lg transition text-sm" title="Sync from Supabase">
                    <i class="fas fa-sync text-xs"></i>
                </a>
            </div>
        </div>
    </form>
</div>

<!-- Users Table -->
<div class="card border border-primary-700">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-primary-900 border-b border-primary-700">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-medium text-primary-400 uppercase tracking-wider">User</th>
                    <th class="px-5 py-3 text-left text-xs font-medium text-primary-400 uppercase tracking-wider">Contact</th>
                    <th class="px-5 py-3 text-left text-xs font-medium text-primary-400 uppercase tracking-wider">Role</th>
                    <th class="px-5 py-3 text-left text-xs font-medium text-primary-400 uppercase tracking-wider">Team</th>
                    <th class="px-5 py-3 text-left text-xs font-medium text-primary-400 uppercase tracking-wider">Joined</th>
                    <th class="px-5 py-3 text-left text-xs font-medium text-primary-400 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-primary-700">
                @forelse($users as $user)
                <tr class="hover:bg-primary-700 transition">
                    <td class="px-5 py-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-primary-700 rounded-full flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-user text-secondary-400 text-sm"></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-primary-100">{{ $user->full_name }}</p>
                                <p class="text-xs text-primary-500">{{ $user->email }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-4">
                        <span class="text-sm text-primary-300">{{ $user->phone ?? 'N/A' }}</span>
                    </td>
                    <td class="px-5 py-4">
                        @if($user->role === 'admin')
                            <span class="px-2 py-1 text-xs rounded-md bg-accent-900 text-accent-300 font-medium">Admin</span>
                        @elseif($user->role === 'rescuer')
                            <span class="px-2 py-1 text-xs rounded-md bg-secondary-900 text-secondary-300 font-medium">Rescuer</span>
                        @else
                            <span class="px-2 py-1 text-xs rounded-md bg-primary-700 text-primary-300 font-medium">Citizen</span>
                        @endif
                    </td>
                    <td class="px-5 py-4">
                        @if($user->rescue_team_id)
                            <span class="text-sm text-secondary-400">
                                <i class="fas fa-users mr-1 text-xs"></i>Assigned
                            </span>
                        @else
                            <span class="text-sm text-primary-500">-</span>
                        @endif
                    </td>
                    <td class="px-5 py-4">
                        <span class="text-xs text-primary-400">{{ $user->created_at->format('M d, Y') }}</span>
                    </td>
                    <td class="px-5 py-4 whitespace-nowrap">
                        <div class="flex items-center space-x-3">
                            <a href="{{ route('admin.users.show', $user) }}" class="text-secondary-400 hover:text-secondary-300 transition" title="View">
                                <i class="fas fa-eye text-sm"></i>
                            </a>
                            <a href="{{ route('admin.users.edit', $user) }}" class="text-secondary-400 hover:text-secondary-300 transition" title="Edit">
                                <i class="fas fa-edit text-sm"></i>
                            </a>
                            @if($user->role !== 'admin')
                            <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline" onsubmit="return confirm('Delete this user?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-accent-400 hover:text-accent-300 transition" title="Delete">
                                    <i class="fas fa-trash text-sm"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-5 py-8 text-center">
                        <i class="fas fa-user-slash text-3xl text-primary-700 mb-2"></i>
                        <p class="text-sm text-primary-500">No users found</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($users->hasPages())
    <div class="p-5 border-t border-primary-700">
        <div class="flex items-center justify-between">
            <div class="text-sm text-primary-400">
                Showing {{ $users->firstItem() }} to {{ $users->lastItem() }} of {{ $users->total() }} users
            </div>
            <div class="flex space-x-2">
                @if($users->onFirstPage())
                    <span class="px-3 py-1.5 text-sm bg-primary-700 text-primary-500 rounded cursor-not-allowed">Previous</span>
                @else
                    <a href="{{ $users->appends(request()->query())->previousPageUrl() }}" class="px-3 py-1.5 text-sm bg-primary-700 hover:bg-primary-600 text-primary-200 rounded transition">Previous</a>
                @endif

                <span class="px-3 py-1.5 text-sm bg-primary-700 text-primary-300">
                    Page {{ $users->currentPage() }} of {{ $users->lastPage() }}
                </span>

                @if($users->hasMorePages())
                    <a href="{{ $users->appends(request()->query())->nextPageUrl() }}" class="px-3 py-1.5 text-sm bg-secondary-400 hover:bg-secondary-500 text-primary-950 rounded transition font-medium">Next</a>
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
</script>
@endpush
