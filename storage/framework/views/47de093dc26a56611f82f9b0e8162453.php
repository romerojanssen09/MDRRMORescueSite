<?php $__env->startSection('title', 'View Report'); ?>
<?php $__env->startSection('header', 'Emergency Report Details'); ?>

<?php $__env->startSection('content'); ?>
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Report Details -->
    <div class="lg:col-span-2 space-y-6">
        <div class="card border border-primary-700 p-6">
            <h3 class="text-lg font-semibold text-primary-100 mb-4">Report Information</h3>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-primary-400 text-xs block mb-1">Emergency Type</label>
                    <p class="font-semibold text-primary-100"><?php echo e($report->emergency_type); ?></p>
                </div>
                <div>
                    <label class="text-primary-400 text-xs block mb-1">Status</label>
                    <p>
                        <?php if($report->status === 'Pending'): ?>
                            <span class="px-3 py-1 text-xs rounded-full bg-accent-900 text-accent-300 font-medium">Pending</span>
                        <?php elseif($report->status === 'In Progress'): ?>
                            <span class="px-3 py-1 text-xs rounded-full bg-secondary-900 text-secondary-300 font-medium">In Progress</span>
                        <?php else: ?>
                            <span class="px-3 py-1 text-xs rounded-full bg-secondary-400 text-primary-950 font-medium">Completed</span>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="col-span-2">
                    <label class="text-primary-400 text-xs block mb-1">Location</label>
                    <p class="font-semibold text-primary-100"><?php echo e($report->location); ?></p>
                </div>
                <?php if($report->latitude && $report->longitude): ?>
                <div class="col-span-2">
                    <label class="text-primary-400 text-xs block mb-1">Coordinates</label>
                    <p class="font-semibold text-primary-200"><?php echo e($report->latitude); ?>, <?php echo e($report->longitude); ?></p>
                </div>
                <?php endif; ?>
                <div class="col-span-2">
                    <label class="text-primary-400 text-xs block mb-1">Description</label>
                    <p class="text-primary-200"><?php echo e($report->description); ?></p>
                </div>
                <div>
                    <label class="text-primary-400 text-xs block mb-1">Reported At</label>
                    <p class="font-semibold text-primary-200"><?php echo e($report->created_at->format('M d, Y H:i A')); ?></p>
                </div>
                <div>
                    <label class="text-primary-400 text-xs block mb-1">Last Updated</label>
                    <p class="font-semibold text-primary-200"><?php echo e($report->updated_at->format('M d, Y H:i A')); ?></p>
                </div>
            </div>

            <?php if($report->photo_url): ?>
            <div class="mt-4">
                <label class="text-primary-400 text-xs block mb-2">Photo</label>
                <img src="<?php echo e($report->photo_url); ?>" alt="Report Photo" class="mt-2 rounded-lg max-w-md border border-primary-700">
            </div>
            <?php endif; ?>
        </div>

        <!-- Map View -->
        <?php if($report->latitude && $report->longitude): ?>
        <div class="card border border-primary-700 p-6">
            <h3 class="text-lg font-semibold text-primary-100 mb-4">
                <i class="fas fa-map-location-dot text-secondary-400 mr-2"></i>Location Map
            </h3>
            <div id="map" style="height: 400px;" class="rounded-lg border border-primary-700 overflow-hidden"></div>
            <div class="mt-3 p-3 bg-secondary-900/20 border border-secondary-900 rounded text-xs text-secondary-300">
                <i class="fas fa-info-circle mr-1"></i> 
                <strong>Emergency Location:</strong> <?php echo e($report->location); ?>

            </div>
        </div>
        <?php endif; ?>

        <!-- Messages -->
        <?php if($report->messages->count() > 0): ?>
        <div class="card border border-primary-700 p-6">
            <h3 class="text-lg font-semibold text-primary-100 mb-4">
                <i class="fas fa-comments text-secondary-400 mr-2"></i>Messages (<?php echo e($report->messages->count()); ?>)
            </h3>
            <div class="space-y-4 max-h-96 overflow-y-auto custom-scrollbar">
                <?php $__currentLoopData = $report->messages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $message): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="border-l-4 border-secondary-400 pl-4 py-2 bg-primary-700 rounded-r">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="font-semibold text-sm text-primary-100"><?php echo e($message->sender->full_name); ?></p>
                            <p class="text-primary-200 text-sm"><?php echo e($message->message); ?></p>
                        </div>
                        <span class="text-xs text-primary-500"><?php echo e($message->created_at->format('M d, H:i')); ?></span>
                    </div>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Citizen Info -->
        <div class="card border border-primary-700 p-6">
            <h3 class="text-base font-semibold text-primary-100 mb-4">
                <i class="fas fa-user text-secondary-400 mr-2"></i>Citizen Information
            </h3>
            <?php if($report->citizen): ?>
                <?php if(!$report->show_name): ?>
                    <div class="mb-3 p-3 bg-accent-900/20 border-l-4 border-accent-400 rounded">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-user-shield text-accent-400"></i>
                            <p class="text-xs text-accent-300 font-semibold">
                                Privacy: Identity hidden from rescuers
                            </p>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="space-y-2 text-sm">
                    <p><span class="text-primary-400">Name:</span> <span class="font-semibold text-primary-100"><?php echo e($report->citizen->full_name); ?></span></p>
                    <p><span class="text-primary-400">Email:</span> <span class="font-semibold text-primary-200"><?php echo e($report->citizen->email); ?></span></p>
                    <?php if($report->citizen->phone): ?>
                    <p><span class="text-primary-400">Phone:</span> <span class="font-semibold text-primary-200"><?php echo e($report->citizen->phone); ?></span></p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="p-4 bg-primary-700 border border-primary-600 rounded">
                    <p class="text-primary-300 text-xs">
                        <i class="fas fa-info-circle text-primary-500 mr-1"></i> 
                        This is a guest report. No registered citizen account.
                    </p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Rescuer/Team Info -->
        <div class="card border border-primary-700 p-6">
            <h3 class="text-base font-semibold text-primary-100 mb-4">
                <i class="fas fa-user-shield text-secondary-400 mr-2"></i>Assignment
            </h3>
            
            <?php if($report->assignedRescuer): ?>
                <div class="mb-4">
                    <label class="text-primary-400 text-xs font-semibold block mb-2">Assigned Rescuer</label>
                    <div class="space-y-2 text-sm">
                        <p><span class="text-primary-400">Name:</span> <span class="font-semibold text-primary-100"><?php echo e($report->assignedRescuer->full_name); ?></span></p>
                        <p><span class="text-primary-400">Email:</span> <span class="font-semibold text-primary-200"><?php echo e($report->assignedRescuer->email); ?></span></p>
                        <?php if($report->assignedRescuer->phone): ?>
                        <p><span class="text-primary-400">Phone:</span> <span class="font-semibold text-primary-200"><?php echo e($report->assignedRescuer->phone); ?></span></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php elseif($report->assignedTeam): ?>
                <div class="mb-4">
                    <label class="text-primary-400 text-xs font-semibold block mb-2">Assigned Team</label>
                    <div class="p-3 bg-secondary-900/20 border border-secondary-900 rounded">
                        <p class="font-semibold text-secondary-300">
                            <i class="fas fa-users mr-1"></i><?php echo e($report->assignedTeam->team_name); ?>

                        </p>
                        <p class="text-xs text-primary-300 mt-1"><?php echo e($report->assignedTeam->specialization); ?></p>
                        <?php if($report->assignedTeam->contact_number): ?>
                        <p class="text-xs text-primary-300 mt-1">
                            <i class="fas fa-phone mr-1"></i><?php echo e($report->assignedTeam->contact_number); ?>

                        </p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <p class="text-primary-500 italic text-sm">No rescuer or team assigned yet</p>
            <?php endif; ?>
        </div>

        <!-- Actions -->
        <div class="card border border-primary-700 p-6">
            <h3 class="text-base font-semibold text-primary-100 mb-4">
                <i class="fas fa-bolt text-secondary-400 mr-2"></i>Actions
            </h3>
            <div class="space-y-2">
                <?php if($report->status === 'Completed'): ?>
                    <button disabled class="block w-full bg-primary-600 text-primary-400 text-center px-4 py-2.5 rounded-lg cursor-not-allowed text-sm font-medium">
                        <i class="fas fa-check-circle mr-2"></i> Report Completed
                    </button>
                    <p class="text-xs text-primary-500 text-center mt-2">
                        <i class="fas fa-info-circle mr-1"></i> Cannot assign teams to completed reports
                    </p>
                <?php else: ?>
                    <a href="<?php echo e(route('admin.reports.assign-map', $report)); ?>" class="block w-full bg-secondary-400 text-primary-950 text-center px-4 py-2.5 rounded-lg hover:bg-secondary-500 transition text-sm font-medium">
                        <i class="fas fa-map-marked-alt mr-2"></i> Assign Rescue Team
                    </a>
                <?php endif; ?>
                <a href="<?php echo e(route('admin.reports.index')); ?>" class="block w-full bg-primary-700 text-primary-200 text-center px-4 py-2.5 rounded-lg hover:bg-primary-600 transition text-sm font-medium">
                    <i class="fas fa-arrow-left mr-2"></i> Back to List
                </a>
            </div>
        </div>
    </div>
</div>

<?php if($report->latitude && $report->longitude): ?>
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
mapboxgl.accessToken = '<?php echo e(env('MAPBOX_ACCESS_TOKEN')); ?>';

const reportLat = <?php echo e($report->latitude); ?>;
const reportLng = <?php echo e($report->longitude); ?>;

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

const emergencyType = '<?php echo e($report->emergency_type); ?>';
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
            <p class="text-sm"><strong>Type:</strong> <?php echo e($report->emergency_type); ?></p>
            <p class="text-sm"><strong>Location:</strong> <?php echo e($report->location); ?></p>
            <p class="text-sm text-gray-600 mt-2"><?php echo e($report->created_at->format('M d, Y H:i A')); ?></p>
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
<?php endif; ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\New folder (4)\MDRRMORescueApp\MDRRMOSite\resources\views\admin\reports\show.blade.php ENDPATH**/ ?>