<div class="table-footer"><span><?= number_format($total) ?> record<?= $total === 1 ? '' : 's' ?> · Page <?= $page ?> of <?= $pages ?></span>
<?php if ($pages > 1): ?><nav class="pagination" aria-label="Results pages">
<?php if ($page > 1): ?><a class="button secondary small" href="<?= e(url($route, array_replace($_GET, ['page' => $page - 1]))) ?>">← Previous</a><?php endif; ?>
<?php if ($page < $pages): ?><a class="button secondary small" href="<?= e(url($route, array_replace($_GET, ['page' => $page + 1]))) ?>">Next →</a><?php endif; ?>
</nav><?php endif; ?></div>
