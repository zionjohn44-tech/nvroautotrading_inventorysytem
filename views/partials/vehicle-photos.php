<div class="photo-field form-field">
    <label for="Photos">Vehicle photos</label>
    <input type="file" id="Photos" name="Photos[]" accept="image/jpeg,image/png,image/webp" aria-describedby="Photos-hint Photos-error" <?= isset($errors['Photos']) ? 'aria-invalid="true"' : '' ?>>
    <small id="Photos-hint">Choose one photo up to 5 MB (JPG, PNG, or WebP). Leave empty to keep the existing photo; selecting a photo replaces it.</small>
    <small id="Photos-error" class="field-error" role="status"><?= e($errors['Photos'] ?? '') ?></small>
    <?php if ($errors): ?><small>Existing photos are kept. Please reselect any new photos before saving again.</small><?php endif; ?>
    <div class="photo-preview" id="photo-preview">
        <?php foreach (\NVRO\Services\VehiclePhotos::paths($data) as $path): ?>
        <figure><img src="<?= e($path) ?>" alt="Saved vehicle photo"><figcaption>Vehicle photo</figcaption></figure>
        <?php endforeach; ?>
    </div>
</div>
