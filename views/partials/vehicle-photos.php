<div class="photo-field form-field">
    <label for="Photos">Vehicle photos</label>
    <input type="file" id="Photos" name="Photos[]" accept="image/jpeg,image/png,image/webp" multiple aria-describedby="Photos-hint Photos-error" <?= isset($errors['Photos']) ? 'aria-invalid="true"' : '' ?>>
    <small id="Photos-hint">Choose 1–3 photos, up to 5 MB each (JPG, PNG, or WebP). The first photo is the cover. Leave empty to keep existing photos; selecting photos replaces the current set.</small>
    <small id="Photos-error" class="field-error" role="status"><?= e($errors['Photos'] ?? '') ?></small>
    <?php if ($errors): ?><small>Existing photos are kept. Please reselect any new photos before saving again.</small><?php endif; ?>
    <div class="photo-preview" id="photo-preview">
        <?php foreach (\NVRO\Services\VehiclePhotos::paths($data) as $index => $path): ?>
        <figure><img src="<?= e($path) ?>" alt="Saved vehicle photo <?= $index + 1 ?>"><figcaption><?= $index === 0 ? 'Cover photo' : 'Photo ' . ($index + 1) ?></figcaption></figure>
        <?php endforeach; ?>
    </div>
</div>
