@extends('layouts.admin')

@section('title', 'View User')
@section('header', $user->full_name)
@section('subtitle', ucfirst($user->role))

@section('content')
<div class="max-w-5xl mx-auto">
    <!-- User Info Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <!-- Role Card -->
        <div class="card p-5 border border-primary-700">
            <p class="text-xs text-primary-400 mb-2">Role</p>
            @if($user->role === 'admin')
                <span class="inline-block px-3 py-1.5 text-sm rounded-md bg-accent-900 text-accent-300 font-medium">
                    <i class="fas fa-user-shield mr-1"></i>Admin
                </span>
            @elseif($user->role === 'rescuer')
                <span class="inline-block px-3 py-1.5 text-sm rounded-md bg-secondary-900 text-secondary-300 font-medium">
                    <i class="fas fa-user-nurse mr-1"></i>Rescuer
                </span>
            @else
                <span class="inline-block px-3 py-1.5 text-sm rounded-md bg-primary-700 text-primary-300 font-medium">
                    <i class="fas fa-user mr-1"></i>Citizen
                </span>
            @endif
        </div>

        <!-- Team Card (for rescuers) -->
        <div class="card p-5 border border-primary-700">
            <p class="text-xs text-primary-400 mb-2">Team Assignment</p>
            @if($user->rescue_team_id && $user->rescueTeam)
                <p class="text-sm font-medium text-secondary-300">
                    <i class="fas fa-users mr-1"></i>{{ $user->rescueTeam->team_name }}
                </p>
                <p class="text-xs text-primary-500 mt-1">{{ $user->rescueTeam->specialization }}</p>
            @else
                <p class="text-sm text-primary-500">Not assigned</p>
            @endif
        </div>

        <!-- Joined Date -->
        <div class="card p-5 border border-primary-700">
            <p class="text-xs text-primary-400 mb-2">Member Since</p>
            <p class="text-sm text-primary-200">{{ $user->created_at->format('M d, Y') }}</p>
            <p class="text-xs text-primary-500 mt-1">{{ $user->created_at->diffForHumans() }}</p>
        </div>
    </div>

    <!-- Contact Information -->
    <div class="card p-6 mb-6 border border-primary-700">
        <h3 class="text-sm font-semibold text-primary-100 mb-4">
            <i class="fas fa-address-card text-secondary-400 mr-2"></i>Contact Information
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <p class="text-xs text-primary-400 mb-1">Email Address</p>
                <p class="text-sm text-primary-200">
                    <i class="fas fa-envelope mr-2 text-secondary-400"></i>{{ $user->email }}
                </p>
            </div>
            <div>
                <p class="text-xs text-primary-400 mb-1">Phone Number</p>
                <p class="text-sm text-primary-200">
                    <i class="fas fa-phone mr-2 text-secondary-400"></i>{{ $user->phone ?? 'Not provided' }}
                </p>
            </div>
        </div>
    </div>

    <!-- Emergency Reports (for citizens) -->
    @if($user->role === 'citizen' && $user->emergencyReports->count() > 0)
    <div class="card p-6 mb-6 border border-primary-700">
        <h3 class="text-sm font-semibold text-primary-100 mb-4">
            <i class="fas fa-exclamation-triangle text-accent-400 mr-2"></i>Emergency Reports ({{ $user->emergencyReports->count() }})
        </h3>
        <div class="space-y-3">
            @foreach($user->emergencyReports as $report)
            <div class="p-4 bg-primary-800 rounded-lg border border-primary-700 hover:border-primary-600 transition">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-2">
                            <h4 class="text-sm font-semibold text-primary-100">{{ $report->emergency_type }}</h4>
                            @if($report->status === 'Pending')
                                <span class="px-2 py-0.5 text-xs rounded-md bg-accent-900 text-accent-300 font-medium">Pending</span>
                            @elseif($report->status === 'In Progress')
                                <span class="px-2 py-0.5 text-xs rounded-md bg-secondary-900 text-secondary-300 font-medium">In Progress</span>
                            @else
                                <span class="px-2 py-0.5 text-xs rounded-md bg-primary-700 text-primary-400 font-medium">{{ $report->status }}</span>
                            @endif
                        </div>
                        <p class="text-xs text-primary-400">
                            <i class="fas fa-map-marker-alt mr-1"></i>{{ $report->location }}
                        </p>
                        <p class="text-xs text-primary-500 mt-1">
                            <i class="fas fa-clock mr-1"></i>{{ $report->created_at->format('M d, Y h:i A') }}
                        </p>
                    </div>
                    <a href="{{ route('admin.reports.show', $report) }}" class="text-secondary-400 hover:text-secondary-300 text-xs">
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Assigned Reports (for rescuers) -->
    @if($user->role === 'rescuer' && $user->assignedReports->count() > 0)
    <div class="card p-6 mb-6 border border-primary-700">
        <h3 class="text-sm font-semibold text-primary-100 mb-4">
            <i class="fas fa-clipboard-list text-secondary-400 mr-2"></i>Assigned Reports ({{ $user->assignedReports->count() }})
        </h3>
        <div class="space-y-3">
            @foreach($user->assignedReports as $report)
            <div class="p-4 bg-primary-800 rounded-lg border border-primary-700 hover:border-primary-600 transition">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-2">
                            <h4 class="text-sm font-semibold text-primary-100">{{ $report->emergency_type }}</h4>
                            @if($report->status === 'Pending')
                                <span class="px-2 py-0.5 text-xs rounded-md bg-accent-900 text-accent-300 font-medium">Pending</span>
                            @elseif($report->status === 'In Progress')
                                <span class="px-2 py-0.5 text-xs rounded-md bg-secondary-900 text-secondary-300 font-medium">In Progress</span>
                            @else
                                <span class="px-2 py-0.5 text-xs rounded-md bg-primary-700 text-primary-400 font-medium">{{ $report->status }}</span>
                            @endif
                        </div>
                        <p class="text-xs text-primary-400">
                            <i class="fas fa-map-marker-alt mr-1"></i>{{ $report->location }}
                        </p>
                        <p class="text-xs text-primary-500 mt-1">
                            <i class="fas fa-clock mr-1"></i>{{ $report->created_at->format('M d, Y h:i A') }}
                        </p>
                    </div>
                    <a href="{{ route('admin.reports.show', $report) }}" class="text-secondary-400 hover:text-secondary-300 text-xs">
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Actions -->
    <div class="flex gap-3">
        <a href="{{ route('admin.users.edit', $user) }}" class="bg-secondary-400 hover:bg-secondary-500 text-primary-950 px-6 py-2.5 rounded-lg transition text-sm font-medium">
            <i class="fas fa-edit mr-2"></i>Edit User
        </a>
        <a href="{{ route('admin.users.index') }}" class="bg-primary-700 hover:bg-primary-600 text-primary-200 px-6 py-2.5 rounded-lg transition text-sm">
            <i class="fas fa-arrow-left mr-2"></i>Back to Users
        </a>
        @if($user->role !== 'admin')
        <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
            @csrf
            @method('DELETE')
            <button type="submit" class="bg-accent-900 hover:bg-accent-800 text-accent-300 px-6 py-2.5 rounded-lg transition text-sm font-medium">
                <i class="fas fa-trash mr-2"></i>Delete User
            </button>
        </form>
        @endif
    </div>
</div>
@endsection
