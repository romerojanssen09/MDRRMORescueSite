<?php $__env->startSection('title', 'Rescue Teams'); ?>
<?php $__env->startSection('header', 'Rescue Teams'); ?>
<?php $__env->startSection('subtitle', 'Manage rescue team operations'); ?>

<?php $__env->startSection('content'); ?>
<!-- Success Message -->
<?php if(session('success')): ?>
<div class="card p-4 mb-6 border border-secondary-900 bg-secondary-900 bg-opacity-20">
    <div class="flex items-center">
        <i class="fas fa-check-circle text-secondary-400 mr-3"></i>
        <p class="text-sm text-secondary-300"><?php echo e(session('success')); ?></p>
    </div>
</div>
<?php endif; ?>

<!-- Filters -->
<div class="card p-5 mb-6 border border-primary-700">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-sm font-semibold text-primary-100">Filters & Actions</h3>
        <?php if(request()->hasAny(['status', 'search'])): ?>
            <a href="<?php echo e(route('admin.teams.index')); ?>" class="text-xs text-secondary-400 hover:text-secondary-300">
                <i class="fas fa-times-circle mr-1"></i>Clear filters
            </a>
        <?php endif; ?>
    </div>
    <form method="GET" id="filter-form" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="block text-xs text-primary-400 mb-2">Status</label>
            <select name="status" class="input-field text-sm w-full" onchange="this.form.submit()">
                <option value="">All Status</option>
                <option value="Available" <?php echo e(request('status') === 'Available' ? 'selected' : ''); ?>>Available</option>
                <option value="On Mission" <?php echo e(request('status') === 'On Mission' ? 'selected' : ''); ?>>On Mission</option>
                <option value="Off Duty" <?php echo e(request('status') === 'Off Duty' ? 'selected' : ''); ?>>Off Duty</option>
            </select>
        </div>
        
        <div>
            <label class="block text-xs text-primary-400 mb-2">Search</label>
            <div class="relative">
                <input type="text" name="search" id="search-input" placeholder="Search teams..." class="input-field text-sm w-full pl-10" value="<?php echo e(request('search')); ?>">
                <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-primary-500 text-xs"></i>
            </div>
        </div>
        
        <div>
            <label class="block text-xs text-primary-400 mb-2">Actions</label>
            <a href="<?php echo e(route('admin.teams.create')); ?>" class="block text-center bg-secondary-400 hover:bg-secondary-500 text-primary-950 px-4 py-2 rounded-lg transition text-sm font-medium">
                <i class="fas fa-plus mr-1 text-xs"></i>Create Team
            </a>
        </div>
    </form>
</div>

<!-- Teams List -->
<div class="space-y-4">
    <?php $__empty_1 = true; $__currentLoopData = $teams; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $team): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
    <div class="card border border-primary-700 hover:border-secondary-400 transition">
        <!-- Team Header -->
        <div class="p-5 flex items-center justify-between cursor-pointer" onclick="toggleTeamMembers('team-<?php echo e($team->id); ?>')">
            <div class="flex items-center flex-1">
                <div class="w-12 h-12 bg-primary-700 rounded-lg flex items-center justify-center mr-4">
                    <i class="fas fa-users text-secondary-400 text-lg"></i>
                </div>
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-1">
                        <h3 class="text-base font-semibold text-primary-100"><?php echo e($team->team_name); ?></h3>
                        <?php if($team->status === 'Available'): ?>
                            <span class="px-2 py-1 text-xs rounded-md bg-secondary-900 text-secondary-300 font-medium">Available</span>
                        <?php elseif($team->status === 'On Mission'): ?>
                            <span class="px-2 py-1 text-xs rounded-md bg-accent-900 text-accent-300 font-medium">On Mission</span>
                        <?php else: ?>
                            <span class="px-2 py-1 text-xs rounded-md bg-primary-700 text-primary-400 font-medium">Off Duty</span>
                        <?php endif; ?>
                    </div>
                    <div class="flex items-center gap-4 text-xs text-primary-400">
                        <span><i class="fas fa-briefcase mr-1"></i><?php echo e($team->specialization); ?></span>
                        <span><i class="fas fa-users mr-1"></i><?php echo e($team->members_count); ?> Members</span>
                        <span><i class="fas fa-clock mr-1"></i><?php echo e($team->created_at->diffForHumans()); ?></span>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="<?php echo e(route('admin.teams.show', $team)); ?>" class="bg-primary-700 hover:bg-primary-600 text-primary-200 px-4 py-2 rounded-lg transition text-xs" onclick="event.stopPropagation()">
                    <i class="fas fa-eye mr-1"></i>View Details
                </a>
                <a href="<?php echo e(route('admin.teams.edit', $team)); ?>" class="bg-secondary-900 hover:bg-secondary-800 text-secondary-300 px-4 py-2 rounded-lg transition text-xs" onclick="event.stopPropagation()">
                    <i class="fas fa-edit mr-1"></i>Edit
                </a>
                <button type="button" class="text-primary-400 hover:text-primary-200 px-2" id="expand-btn-<?php echo e($team->id); ?>">
                    <i class="fas fa-chevron-down transition-transform"></i>
                </button>
            </div>
        </div>

        <!-- Team Members (Expandable) -->
        <div id="team-<?php echo e($team->id); ?>" class="hidden border-t border-primary-700 bg-primary-900 bg-opacity-30">
            <div class="p-5">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="text-sm font-semibold text-primary-100">
                        <i class="fas fa-users text-secondary-400 mr-2"></i>Team Members (<?php echo e($team->members->count()); ?>)
                    </h4>
                </div>
                
                <?php if($team->members->count() > 0): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                    <?php $__currentLoopData = $team->members; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $member): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="flex items-center p-3 bg-primary-800 rounded-lg border border-primary-700">
                        <div class="w-10 h-10 bg-primary-700 rounded-full flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-user text-secondary-400 text-sm"></i>
                        </div>
                        <div class="ml-3 flex-1 min-w-0">
                            <p class="text-sm font-medium text-primary-100 truncate"><?php echo e($member->full_name); ?></p>
                            <p class="text-xs text-primary-400 truncate"><?php echo e($member->email); ?></p>
                        </div>
                    </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
                <?php else: ?>
                <div class="text-center py-6">
                    <i class="fas fa-user-slash text-2xl text-primary-700 mb-2"></i>
                    <p class="text-sm text-primary-500">No members assigned</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
    <div class="card p-12 border border-primary-700 text-center">
        <i class="fas fa-users-slash text-4xl text-primary-700 mb-3"></i>
        <p class="text-sm text-primary-500">No rescue teams found</p>
        <a href="<?php echo e(route('admin.teams.create')); ?>" class="inline-block mt-4 bg-secondary-400 hover:bg-secondary-500 text-primary-950 px-6 py-2 rounded-lg transition text-sm font-medium">
            <i class="fas fa-plus mr-2"></i>Create First Team
        </a>
    </div>
    <?php endif; ?>
</div>

<!-- Pagination -->
<?php if($teams->hasPages()): ?>
<div class="mt-6 card p-5 border border-primary-700">
    <div class="flex items-center justify-between">
        <div class="text-sm text-primary-400">
            Showing <?php echo e($teams->firstItem()); ?> to <?php echo e($teams->lastItem()); ?> of <?php echo e($teams->total()); ?> teams
        </div>
        <div class="flex space-x-2">
            <?php if($teams->onFirstPage()): ?>
                <span class="px-3 py-1.5 text-sm bg-primary-700 text-primary-500 rounded cursor-not-allowed">Previous</span>
            <?php else: ?>
                <a href="<?php echo e($teams->appends(request()->query())->previousPageUrl()); ?>" class="px-3 py-1.5 text-sm bg-primary-700 hover:bg-primary-600 text-primary-200 rounded transition">Previous</a>
            <?php endif; ?>

            <span class="px-3 py-1.5 text-sm bg-primary-700 text-primary-300">
                Page <?php echo e($teams->currentPage()); ?> of <?php echo e($teams->lastPage()); ?>

            </span>

            <?php if($teams->hasMorePages()): ?>
                <a href="<?php echo e($teams->appends(request()->query())->nextPageUrl()); ?>" class="px-3 py-1.5 text-sm bg-secondary-400 hover:bg-secondary-500 text-primary-950 rounded transition font-medium">Next</a>
            <?php else: ?>
                <span class="px-3 py-1.5 text-sm bg-primary-700 text-primary-500 rounded cursor-not-allowed">Next</span>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
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
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\New folder (4)\MDRRMORescueApp\MDRRMOSite\resources\views\admin\teams\index.blade.php ENDPATH**/ ?>