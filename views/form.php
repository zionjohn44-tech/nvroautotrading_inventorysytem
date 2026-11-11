<div class="form-page"><a class="back-link" href="<?= e(url($cancelRoute ?? $returnRoute)) ?>">← Back</a>
<section class="panel form-panel"><div class="panel-heading"><h2>Details</h2><span class="muted">* Required fields</span></div>
<form method="post" class="record-form" data-dirty-form <?= !empty($vehiclePhotos) ? 'enctype="multipart/form-data"' : '' ?>>
<?= csrf() ?>
<?php if (isset($inquiryCustomers)) { require __DIR__ . '/partials/inquiry-customer.php'; } ?>
<?php require __DIR__ . '/partials/fields.php'; ?>
<?php if (!empty($vehiclePhotos)) { require __DIR__ . '/partials/vehicle-photos.php'; } ?>
<div class="form-actions"><?php if (!empty($canMarkSold)): ?><button class="button sold-button" type="submit" name="mark_sold" value="1" data-confirm="Save these changes and mark this vehicle as sold?">Mark as sold</button><?php endif; ?><a class="button secondary" href="<?= e(url($cancelRoute ?? $returnRoute)) ?>">Cancel</a><button class="button primary" type="submit"><?= e($submit ?? 'Save changes') ?> <span aria-hidden="true">→</span></button></div>
</form></section>
</div>
