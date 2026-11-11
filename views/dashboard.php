<div class="dashboard-stack">
<div class="stat-grid dashboard-stats" aria-label="Dealership summary">
<?php foreach ([['available', 'Available vehicles', 'Ready for the next owner', 'inventory', ['status' => 'Available'], 'green', 'inventory'], ['sold', 'Sold vehicles', 'Retained in your records', 'inventory', ['status' => 'Sold'], 'navy', 'sold'], ['inquiries', 'Open inquiries', 'Pending and in progress', 'inquiries', [], 'amber', 'inquiries'], ['drives', 'Pending test drives', 'Waiting for approval', 'test-drives', ['status' => 'Pending'], 'red', 'test-drives']] as [$key, $label, $hint, $target, $params, $color, $symbol]): ?>
<a class="stat-card stat-<?= e($color) ?>" data-stat="<?= e($key) ?>" href="<?= e(url($target, $params)) ?>"><div class="stat-top"><span class="stat-label"><?= icon($symbol) ?><?= e($label) ?></span><span class="stat-open" aria-hidden="true">↗</span></div><div class="stat-value"><strong><?= number_format($stats[$key]) ?></strong><span class="stat-value-icon"><?= icon($symbol) ?></span></div><div class="stat-bottom"><span><?= e($hint) ?></span></div></a>
<?php endforeach; ?>
</div>

<section class="panel dashboard-recent" aria-labelledby="recent-heading">
    <div class="panel-heading"><div><h2 id="recent-heading">Recent inventory</h2><p>Your latest vehicles · <?= number_format($stats['total']) ?> in total</p></div><a class="text-link" href="<?= e(url('inventory')) ?>">View all →</a></div>
    <?php if ($vehicles): ?><div class="dashboard-vehicles">
    <?php foreach ($vehicles as $v): $photos = \NVRO\Services\VehiclePhotos::paths($v); ?>
        <a class="dashboard-vehicle" href="<?= e(url('vehicle', ['id' => $v['Vehicle_ID']])) ?>">
            <div class="dashboard-vehicle-photo"><?php if ($photos): ?><img src="<?= e($photos[0]) ?>" alt="<?= e($v['Brand_Name'] . ' ' . $v['Model']) ?>" loading="lazy"><?php else: ?><div class="photo-placeholder"><?= icon('inventory') ?><span class="dashboard-no-photo">No photo yet</span></div><?php endif; ?><?= badge($v['Availability_Status']) ?></div>
            <div class="dashboard-vehicle-copy"><small><?= e($v['Year']) ?> · #<?= e($v['Vehicle_ID']) ?></small><h3><?= e($v['Brand_Name'] . ' ' . $v['Model']) ?></h3><p><?= e($v['Color'] . ' · ' . $v['Transmission']) ?></p><strong><?= money($v['Price']) ?></strong></div>
        </a>
    <?php endforeach; ?>
    </div><?php else: ?><div class="empty-state"><span class="empty-icon"><?= icon('inventory') ?></span><h3>Your inventory starts here</h3><p>Add your first vehicle and its photos to get started.</p><a class="button primary" href="<?= e(url('vehicle-new')) ?>">+ Add vehicle</a></div><?php endif; ?>
</section>

<section class="panel dashboard-drives" aria-labelledby="drives-heading">
    <div class="panel-heading"><div><h2 id="drives-heading">Upcoming test drives</h2><p>Your pending queue and next appointments, all in one place.</p></div><a class="text-link" href="<?= e(url('test-drives')) ?>">View all →</a></div>
    <div class="dashboard-queue-section">
        <div class="dashboard-subheading"><div><h3>Next in the queue</h3><p>First <?= count($pendingPreview) ?> of <?= e($stats['drives']) ?> pending · First come, first served</p></div><a class="text-link" href="<?= e(url('test-drives', ['status' => 'Pending'])) ?>">Open queue →</a></div>
        <?php if ($pendingPreview): ?><ol class="dashboard-queue-grid">
        <?php foreach ($pendingPreview as $position => $drive): ?>
            <li class="dashboard-queue-item <?= $position === 0 ? 'is-first' : '' ?>" data-request-id="<?= e($drive['Test_Drive_ID']) ?>"><div class="dashboard-queue-position"><span><?= $position + 1 ?></span><small><?= $position === 0 ? 'Next in line' : 'Waiting' ?></small></div><h4><?= e($drive['Customer_Name']) ?></h4><p><?= e($drive['Year'] . ' ' . $drive['Brand_Name'] . ' ' . $drive['Model']) ?></p><time datetime="<?= e(date('c', strtotime($drive['Scheduled_Date']))) ?>"><?= e(date('M j · g:i A', strtotime($drive['Scheduled_Date']))) ?></time><a class="text-link" href="<?= e(url('test-drive-edit', ['id' => $drive['Test_Drive_ID']])) ?>">View request #<?= e($drive['Test_Drive_ID']) ?> →</a></li>
        <?php endforeach; ?>
        </ol><?php else: ?><div class="dashboard-empty"><strong>The queue is clear.</strong><span>New pending requests will appear here.</span></div><?php endif; ?>
    </div>
    <div class="dashboard-schedule-section">
        <div class="dashboard-subheading"><div><h3>Scheduled appointments</h3><p>Next five by appointment date · Philippine time (UTC+8)</p></div><a class="text-link" href="<?= e(url('test-drive-new')) ?>">+ New request</a></div>
        <?php if ($upcoming): ?><div class="dashboard-schedule">
        <?php foreach ($upcoming as $drive): ?><a class="dashboard-appointment" data-request-id="<?= e($drive['Test_Drive_ID']) ?>" href="<?= e(url('test-drive-edit', ['id' => $drive['Test_Drive_ID']])) ?>"><div class="date-tile"><span><?= e(date('M', strtotime($drive['Scheduled_Date']))) ?></span><strong><?= e(date('d', strtotime($drive['Scheduled_Date']))) ?></strong></div><div class="dashboard-appointment-person"><strong><?= e($drive['Customer_Name']) ?></strong><p><?= e($drive['Brand_Name'] . ' ' . $drive['Model']) ?></p></div><time datetime="<?= e(date('c', strtotime($drive['Scheduled_Date']))) ?>"><?= e(date('D, M j, Y', strtotime($drive['Scheduled_Date']))) ?><small><?= e(date('g:i A', strtotime($drive['Scheduled_Date']))) ?> · <?= e($drive['Duration_Minutes']) ?> min</small></time><?= badge($drive['Request_Status']) ?></a><?php endforeach; ?>
        </div><?php else: ?><div class="dashboard-empty"><strong>No upcoming appointments.</strong><span>Your next scheduled test drives will appear here.</span></div><?php endif; ?>
    </div>
</section>
</div>