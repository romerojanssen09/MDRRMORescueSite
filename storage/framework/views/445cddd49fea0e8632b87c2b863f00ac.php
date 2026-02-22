<?php $__env->startSection('title', 'View Rescue Team'); ?>
<?php $__env->startSection('header', $team->team_name); ?>
<?php $__env->startSection('subtitle', $team->specialization); ?>

<?php $__env->startSection('content'); ?>
<!-- Success/Error Messages -->
<?php if(session('success')): ?>
<div class="card p-4 mb-6 border border-secondary-900 bg-secondary-900 bg-opacity-20">
    <div class="flex items-center">
        <i class="fas fa-check-circle text-secondary-400 mr-3"></i>
        <p class="text-sm text-secondary-300"><?php echo e(session('success')); ?></p>
    </div>
</div>
<?php endif; ?>

<?php if(session('error')): ?>
<div class="card p-4 mb-6 border border-accent-900 bg-accent-900 bg-opacity-20">
    <div class="flex items-center">
        <i class="fas fa-exclamation-circle text-accent-400 mr-3"></i>
        <p class="text-sm text-accent-300"><?php echo e(session('error')); ?></p>
    </div>
</div>
<?php endif; ?>

<div class="max-w-6xl mx-auto">
    <!-- Team Info -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- Status Card -->
        <div class="card p-5 border border-primary-700">
            <p class="text-xs text-primary-400 mb-2">Status</p>
            <?php if($team->status === 'Available'): ?>
                <span class="inline-block px-3 py-1.5 text-sm rounded-md bg-secondary-900 text-secondary-300 font-medium">
                    <i class="fas fa-circle-check mr-1"></i>Available
                </span>
            <?php elseif($team->status === 'On Mission'): ?>
                <span class="inline-block px-3 py-1.5 text-sm rounded-md bg-accent-900 text-accent-300 font-medium">
                    <i class="fas fa-truck-medical mr-1"></i>On Mission
                </span>
            <?php else: ?>
                <span class="inline-block px-3 py-1.5 text-sm rounded-md bg-primary-700 text-primary-400 font-medium">
                    <i class="fas fa-moon mr-1"></i>Off Duty
                </span>
            <?php endif; ?>
        </div>

        <!-- Members Count -->
        <div class="card p-5 border border-primary-700">
            <p class="text-xs text-primary-400 mb-2">Team Members</p>
            <p class="text-2xl font-bold text-primary-100"><?php echo e($team->members_count); ?></p>
        </div>

        <!-- Created Date -->
        <div class="card p-5 border border-primary-700">
            <p class="text-xs text-primary-400 mb-2">Created</p>
            <p class="text-sm text-primary-200"><?php echo e($team->created_at->format('M d, Y')); ?></p>
            <p class="text-xs text-primary-500 mt-1"><?php echo e($team->created_at->diffForHumans()); ?></p>
        </div>
    </div>

    <!-- Location Info -->
    <?php if($team->province || $team->municipality || $team->barangay): ?>
    <div class="card p-5 mb-6 border border-primary-700">
        <h3 class="text-sm font-semibold text-primary-100 mb-3">
            <i class="fas fa-map-marker-alt text-secondary-400 mr-2"></i>Location
        </h3>
        <p class="text-sm text-primary-200">
            <?php echo e($team->street_address ? $team->street_address . ', ' : ''); ?>

            <?php echo e($team->barangay); ?>, <?php echo e($team->municipality); ?>, <?php echo e($team->province); ?>

        </p>
        <?php if($team->latitude && $team->longitude): ?>
        <p class="text-xs text-primary-500 mt-2">
            <i class="fas fa-location-dot mr-1"></i>
            <?php echo e(number_format($team->latitude, 6)); ?>, <?php echo e(number_format($team->longitude, 6)); ?>

        </p>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Team Members -->
    <div class="card p-6 border border-primary-700 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-primary-100">
                <i class="fas fa-users text-secondary-400 mr-2"></i>Team Members (<?php echo e($team->members->count()); ?>)
            </h3>
            <?php if($team->members->count() > 0 && \App\Models\RescueTeam::where('id', '!=', $team->id)->exists()): ?>
            <button type="button" onclick="toggleReassignMode()" id="reassignBtn" class="text-xs bg-primary-700 hover:bg-primary-600 text-primary-200 px-4 py-2 rounded-lg transition">
                <i class="fas fa-exchange-alt mr-1"></i>Reassign Members
            </button>
            <?php endif; ?>
        </div>

        <!-- Reassign Form (Hidden by default) -->
        <?php if(\App\Models\RescueTeam::where('id', '!=', $team->id)->exists()): ?>
        <form id="reassignForm" action="<?php echo e(route('admin.teams.reassign-members', $team)); ?>" method="POST" class="hidden mb-4" onsubmit="return validateReassignment()">
            <?php echo csrf_field(); ?>
            <div class="bg-secondary-900 bg-opacity-20 border border-secondary-900 rounded-lg p-4 mb-3">
                <p class="text-xs text-secondary-300 mb-3">
                    <i class="fas fa-info-circle mr-1"></i>
                    Select one or more members below and choose the destination team
                </p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs text-primary-400 mb-2">Destination Team *</label>
                        <select name="destination_team_id" id="destinationTeam" class="input-field text-sm w-full" required>
                            <option value="">Select team...</option>
                            <?php $__currentLoopData = \App\Models\RescueTeam::where('id', '!=', $team->id)->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $otherTeam): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($otherTeam->id); ?>">
                                    <?php echo e($otherTeam->team_name); ?> - <?php echo e($otherTeam->specialization); ?> (<?php echo e($otherTeam->members_count); ?> members)
                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="flex items-end gap-2">
                        <button type="submit" id="confirmReassignBtn" class="flex-1 bg-secondary-400 hover:bg-secondary-500 text-primary-950 px-4 py-2 rounded-lg transition text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                            <i class="fas fa-check mr-1"></i>Reassign <span id="selectedCount">0</span> Member(s)
                        </button>
                        <button type="button" onclick="toggleReassignMode()" class="bg-primary-700 hover:bg-primary-600 text-primary-200 px-4 py-2 rounded-lg transition text-sm">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
        </form>
        <?php endif; ?>

        <!-- Members List -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <?php $__empty_1 = true; $__currentLoopData = $team->members; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $member): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <div class="flex items-center p-3 bg-primary-800 rounded-lg border border-primary-700 hover:border-primary-600 transition">
                <div class="reassign-checkbox hidden mr-3">
                    <input type="checkbox" 
                           name="member_ids[]" 
                           value="<?php echo e($member->id); ?>" 
                           form="reassignForm"
                           onchange="updateReassignButton()"
                           class="w-4 h-4 text-secondary-400 bg-primary-700 border-primary-600 rounded focus:ring-secondary-400 focus:ring-2 member-checkbox">
                </div>
                <div class="w-10 h-10 bg-primary-700 rounded-full flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-user text-secondary-400 text-sm"></i>
                </div>
                <div class="ml-3 flex-1 min-w-0">
                    <p class="text-sm font-medium text-primary-100 truncate"><?php echo e($member->full_name); ?></p>
                    <p class="text-xs text-primary-400 truncate"><?php echo e($member->email); ?></p>
                    <?php if($member->phone): ?>
                        <p class="text-xs text-primary-500 mt-1">
                            <i class="fas fa-phone mr-1"></i><?php echo e($member->phone); ?>

                        </p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="col-span-full text-center py-8">
                <i class="fas fa-user-slash text-3xl text-primary-700 mb-2"></i>
                <p class="text-sm text-primary-500">No members assigned to this team</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Actions -->
    <div class="flex gap-3">
        <a href="<?php echo e(route('admin.teams.edit', $team)); ?>" class="bg-secondary-400 hover:bg-secondary-500 text-primary-950 px-6 py-2.5 rounded-lg transition text-sm font-medium">
            <i class="fas fa-edit mr-2"></i>Edit Team
        </a>
        <a href="<?php echo e(route('admin.teams.index')); ?>" class="bg-primary-700 hover:bg-primary-600 text-primary-200 px-6 py-2.5 rounded-lg transition text-sm">
            <i class="fas fa-arrow-left mr-2"></i>Back to Teams
        </a>
        <form action="<?php echo e(route('admin.teams.destroy', $team)); ?>" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this team? All members will be unassigned.')">
            <?php echo csrf_field(); ?>
            <?php echo method_field('DELETE'); ?>
            <button type="submit" class="bg-accent-900 hover:bg-accent-800 text-accent-300 px-6 py-2.5 rounded-lg transition text-sm font-medium">
                <i class="fas fa-trash mr-2"></i>Delete Team
            </button>
        </form>
    </div>
</div>

<script>
function toggleReassignMode() {
    const form = document.getElementById('reassignForm');
    const btn = document.getElementById('reassignBtn');
    const checkboxes = document.querySelectorAll('.reassign-checkbox');
    
    if (form.classList.contains('hidden')) {
        // Enter reassign mode
        form.classList.remove('hidden');
        checkboxes.forEach(cb => cb.classList.remove('hidden'));
        btn.innerHTML = '<i class="fas fa-times mr-1"></i>Cancel Reassignment';
        btn.classList.remove('bg-primary-700', 'hover:bg-primary-600');
        btn.classList.add('bg-accent-900', 'hover:bg-accent-800', 'text-accent-300');
    } else {
        // Exit reassign mode
        form.classList.add('hidden');
        checkboxes.forEach(cb => {
            cb.classList.add('hidden');
            cb.querySelector('input').checked = false;
        });
        btn.innerHTML = '<i class="fas fa-exchange-alt mr-1"></i>Reassign Members';
        btn.classList.remove('bg-accent-900', 'hover:bg-accent-800', 'text-accent-300');
        btn.classList.add('bg-primary-700', 'hover:bg-primary-600');
        updateReassignButton();
    }
}

function updateReassignButton() {
    const checkedCount = document.querySelectorAll('.member-checkbox:checked').length;
    const confirmBtn = document.getElementById('confirmReassignBtn');
    const countSpan = document.getElementById('selectedCount');
    
    countSpan.textContent = checkedCount;
    
    if (checkedCount > 0) {
        confirmBtn.disabled = false;
    } else {
        confirmBtn.disabled = true;
    }
}

function validateReassignment() {
    const checkedCount = document.querySelectorAll('.member-checkbox:checked').length;
    const destinationTeam = document.getElementById('destinationTeam');
    
    if (checkedCount === 0) {
        alert('Please select at least one member to reassign');
        return false;
    }
    
    if (!destinationTeam.value) {
        alert('Please select a destination team');
        return false;
    }
    
    const teamName = destinationTeam.options[destinationTeam.selectedIndex].text;
    return confirm(`Are you sure you want to reassign ${checkedCount} member(s) to ${teamName}?`);
}
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\New folder (4)\MDRRMORescueApp\MDRRMOSite\resources\views\admin\teams\show.blade.php ENDPATH**/ ?>