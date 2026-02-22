<?php $__env->startSection('title', 'Assign Rescuer'); ?>
<?php $__env->startSection('header', 'Assign Rescuer to Emergency Report'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow p-6">
        <!-- Report Details -->
        <div class="mb-6 p-4 bg-gray-50 rounded">
            <h3 class="font-bold text-lg mb-2">Report Details</h3>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="text-gray-600">Type:</span>
                    <span class="font-semibold ml-2"><?php echo e($report->emergency_type); ?></span>
                </div>
                <div>
                    <span class="text-gray-600">Status:</span>
                    <span class="ml-2 px-2 py-1 text-xs rounded-full 
                        <?php echo e($report->status === 'Pending' ? 'bg-orange-100 text-orange-800' : ''); ?>

                        <?php echo e($report->status === 'In Progress' ? 'bg-teal-100 text-teal-800' : ''); ?>

                        <?php echo e($report->status === 'Completed' ? 'bg-green-100 text-green-800' : ''); ?>">
                        <?php echo e($report->status); ?>

                    </span>
                </div>
                <div class="col-span-2">
                    <span class="text-gray-600">Location:</span>
                    <span class="ml-2"><?php echo e($report->location); ?></span>
                </div>
                <?php if($report->citizen): ?>
                <div class="col-span-2">
                    <span class="text-gray-600">Citizen:</span>
                    <span class="ml-2"><?php echo e($report->citizen->full_name); ?> (<?php echo e($report->citizen->phone); ?>)</span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <form action="<?php echo e(route('admin.reports.update', $report)); ?>" method="POST">
            <?php echo csrf_field(); ?>
            <?php echo method_field('PUT'); ?>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">
                    Assignment Type <span class="text-red-500">*</span>
                </label>
                <div class="flex gap-4 mb-2">
                    <label class="flex items-center">
                        <input type="radio" name="assignment_type" value="rescuer" class="mr-2" 
                            <?php echo e(old('assignment_type', $report->assigned_rescuer_id ? 'rescuer' : 'team') === 'rescuer' ? 'checked' : ''); ?>

                            onchange="toggleAssignment()">
                        <span>Assign Individual Rescuer</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="assignment_type" value="team" class="mr-2"
                            <?php echo e(old('assignment_type', $report->assigned_team_id ? 'team' : '') === 'team' ? 'checked' : ''); ?>

                            onchange="toggleAssignment()">
                        <span>Assign Rescue Team</span>
                    </label>
                </div>
            </div>

            <div id="rescuer-section" class="mb-4" style="display: none;">
                <label class="block text-gray-700 text-sm font-bold mb-2">
                    Select Rescuer <span class="text-red-500">*</span>
                </label>
                <select name="assigned_rescuer_id" class="w-full border rounded px-3 py-2 <?php $__errorArgs = ['assigned_rescuer_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                    <option value="">-- Select Rescuer --</option>
                    <?php $__currentLoopData = $rescuers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rescuer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($rescuer->id); ?>" <?php echo e($report->assigned_rescuer_id === $rescuer->id ? 'selected' : ''); ?>>
                            <?php echo e($rescuer->full_name); ?> (<?php echo e($rescuer->email); ?>)
                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <?php $__errorArgs = ['assigned_rescuer_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div id="team-section" class="mb-4" style="display: none;">
                <label class="block text-gray-700 text-sm font-bold mb-2">
                    Select Rescue Team <span class="text-red-500">*</span>
                </label>
                <select name="assigned_team_id" class="w-full border rounded px-3 py-2 <?php $__errorArgs = ['assigned_team_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                    <option value="">-- Select Team --</option>
                    <?php $__currentLoopData = $teams; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $team): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($team->id); ?>" <?php echo e($report->assigned_team_id === $team->id ? 'selected' : ''); ?>>
                            <?php echo e($team->name); ?> - <?php echo e($team->specialization); ?> (<?php echo e($team->status); ?>)
                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <?php $__errorArgs = ['assigned_team_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                
                <?php if($report->assigned_team_id): ?>
                <p class="text-sm text-blue-600 mt-2">
                    <i class="fas fa-info-circle"></i> Currently assigned to team: <?php echo e($report->assignedTeam->name ?? 'Unknown'); ?>

                </p>
                <?php endif; ?>
            </div>

            <div class="bg-yellow-50 border border-yellow-200 rounded p-4 mb-4">
                <p class="text-sm text-yellow-800">
                    <i class="fas fa-bell"></i> <strong>Notifications will be sent to:</strong>
                </p>
                <ul class="text-sm text-yellow-700 mt-2 ml-4 list-disc" id="notification-info">
                    <li>Citizen: "A rescuer/team has been assigned to your emergency"</li>
                    <li id="rescuer-notif">Rescuer: "You have been assigned to a new emergency"</li>
                    <li id="team-notif" style="display: none;">All team members (who are logged in): "Your team has been assigned to a new emergency"</li>
                </ul>
            </div>

            <div class="flex gap-4">
                <button type="submit" class="bg-blue-500 text-white px-6 py-2 rounded hover:bg-blue-600">
                    <i class="fas fa-user-plus"></i> Assign
                </button>
                <a href="<?php echo e(route('admin.reports.assign-map', $report)); ?>" class="bg-green-500 text-white px-6 py-2 rounded hover:bg-green-600">
                    <i class="fas fa-map-marked-alt"></i> View on Map
                </a>
                <a href="<?php echo e(route('admin.reports.show', $report)); ?>" class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>

        <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded">
            <p class="text-sm text-blue-800">
                <i class="fas fa-info-circle"></i> <strong>Note:</strong> Only rescuers can mark reports as "Completed". Admin can only assign rescuers or teams.
            </p>
        </div>
    </div>
</div>

<script>
function toggleAssignment() {
    const assignmentType = document.querySelector('input[name="assignment_type"]:checked').value;
    const rescuerSection = document.getElementById('rescuer-section');
    const teamSection = document.getElementById('team-section');
    const rescuerNotif = document.getElementById('rescuer-notif');
    const teamNotif = document.getElementById('team-notif');
    
    if (assignmentType === 'rescuer') {
        rescuerSection.style.display = 'block';
        teamSection.style.display = 'none';
        rescuerNotif.style.display = 'list-item';
        teamNotif.style.display = 'none';
        
        // Make rescuer required, team optional
        document.querySelector('select[name="assigned_rescuer_id"]').required = true;
        document.querySelector('select[name="assigned_team_id"]').required = false;
    } else {
        rescuerSection.style.display = 'none';
        teamSection.style.display = 'block';
        rescuerNotif.style.display = 'none';
        teamNotif.style.display = 'list-item';
        
        // Make team required, rescuer optional
        document.querySelector('select[name="assigned_rescuer_id"]').required = false;
        document.querySelector('select[name="assigned_team_id"]').required = true;
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleAssignment();
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\New folder (4)\MDRRMORescueApp\MDRRMOSite\resources\views\admin\reports\edit.blade.php ENDPATH**/ ?>