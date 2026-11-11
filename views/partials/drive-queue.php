<section class="drive-queue" aria-labelledby="queue-heading">
    <div class="drive-queue-header">
        <div><div class="queue-title-row"><span class="queue-live-dot" aria-hidden="true"></span><h2 id="queue-heading">Pending test-drive queue</h2><span class="queue-count"><?= e($pendingCount) ?> waiting</span></div><p>First come, first served. Approve requests in the order they were received.</p></div>
        <?php if ($next): ?>
        <form method="post" action="<?= e(url('test-drive-next')) ?>" data-confirm="<?= e('Approve request #' . $next['Test_Drive_ID'] . ' for ' . $next['Customer_Name'] . '? Vehicle availability and schedule will be checked again.') ?>">
            <?= csrf() ?><button class="button primary queue-process" type="submit"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m13 3-8 11h6l-1 7 9-12h-7l1-6Z"/></svg>Process next <span aria-hidden="true">→</span></button>
        </form>
        <?php endif; ?>
    </div>
    <?php if ($pendingRequests): ?>
    <div class="queue-strip" id="pending-queue" role="region" aria-label="Pending requests in arrival order. Scroll to see more." tabindex="0">
        <ol class="queue-cards">
        <?php foreach ($pendingRequests as $position => $request):
            $scheduled = strtotime($request['Scheduled_Date']);
            $needsAttention = $scheduled <= time() || $request['Availability_Status'] !== 'Available';
        ?>
            <li class="queue-card <?= $position === 0 ? 'queue-card-front' : '' ?>" data-request-id="<?= e($request['Test_Drive_ID']) ?>">
                <div class="queue-card-top"><span class="queue-position"><?= $position === 0 ? 'Next in line' : 'Position ' . ($position + 1) ?></span><span class="queue-request-id">#<?= e($request['Test_Drive_ID']) ?></span></div>
                <h3><?= e($request['Customer_Name']) ?></h3>
                <a class="queue-vehicle" href="<?= e(url('vehicle', ['id' => $request['Vehicle_ID']])) ?>"><?= e($request['Year'] . ' ' . $request['Brand_Name'] . ' ' . $request['Model']) ?></a>
                <div class="queue-detail"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v4m10-4v4M3 11h18"/></svg><time datetime="<?= e(date('c', $scheduled)) ?>"><?= e(date('M j, Y', $scheduled)) ?><span><?= e(date('g:i A', $scheduled)) ?> · <?= e($request['Duration_Minutes']) ?> min</span></time></div>
                <div class="queue-detail"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m7 3 3 5-3 3a16 16 0 0 0 6 6l3-3 5 3-2 4C10 22 2 14 3 5l4-2Z"/></svg><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $request['Contact_Number'])) ?>"><?= e($request['Contact_Number']) ?></a></div>
                <div class="queue-card-footer"><?php if ($needsAttention): ?><span class="queue-attention">Needs attention</span><?php else: ?><span class="queue-status"><?= $position === 0 ? 'Ready for approval' : 'Waiting for approval' ?></span><?php endif; ?><a href="<?= e(url('test-drive-edit', ['id' => $request['Test_Drive_ID']])) ?>">Reschedule<span class="sr-only"> request #<?= e($request['Test_Drive_ID']) ?></span></a></div>
                <?php if ($needsAttention): ?><p class="queue-warning"><?= $scheduled <= time() ? 'This appointment has passed. Reschedule or cancel it to keep the queue moving.' : 'This vehicle is unavailable. Reschedule or cancel this request.' ?></p><?php endif; ?>
            </li>
        <?php endforeach; ?>
        </ol>
    </div>
    <div class="queue-bottom"><span><strong>FIFO</strong> · Ordered by request arrival, not appointment date. All times UTC+8.</span><div class="queue-navigation" hidden><button type="button" class="queue-scroll" data-queue-direction="-1" aria-label="Previous queued requests" aria-controls="pending-queue">←</button><button type="button" class="queue-scroll" data-queue-direction="1" aria-label="Next queued requests" aria-controls="pending-queue">→</button></div></div>
    <?php else: ?>
    <div class="queue-clear"><span aria-hidden="true">✓</span><div><h3>The queue is clear</h3><p>New requests will appear here. Approved appointments are listed below.</p></div><a class="button secondary" href="<?= e(url('test-drive-new')) ?>">+ New request</a></div>
    <?php endif; ?>
</section>
