<?php if ($errors): ?>
<div class="alert error" role="alert" tabindex="-1" id="validation-summary"><strong>Please check the following:</strong><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>
<div class="form-grid">
<?php foreach ($fields as $field):
    $name = $field['name']; $type = $field['type'] ?? 'text';
    $value = $type === 'password' ? '' : ($data[$name] ?? '');
    $required = $field['required'] ?? true;
    $descriptions = [];
    if (isset($field['hint'])) { $descriptions[] = $name . '-hint'; }
    if (isset($errors[$name])) { $descriptions[] = $name . '-error'; }
?>
<div class="form-field <?= $type === 'textarea' ? 'full-width' : '' ?>">
    <label for="<?= e($type === 'brand' ? 'Existing_Brand' : $name) ?>"><?= e($field['label']) ?><?php if ($required): ?> <span class="required" aria-hidden="true">*</span><?php endif; ?></label>
    <?php if ($type === 'brand'): ?>
    <?php $chosenBrand = $data['Existing_Brand'] ?? (in_array($value, $field['options'], true) ? $value : ''); ?>
    <select id="Existing_Brand" name="Existing_Brand" aria-describedby="Brand_Name-hint">
        <option value="">+ Add a new brand</option>
        <?php foreach ($field['options'] as $brand): ?><option value="<?= e($brand) ?>"<?= selected($chosenBrand, $brand) ?>><?= e($brand) ?></option><?php endforeach; ?>
    </select>
    <div id="new-brand-field"><label for="Brand_Name">New brand name</label><input id="Brand_Name" name="Brand_Name" value="<?= e($value) ?>" maxlength="50" required placeholder="e.g. Toyota" aria-describedby="Brand_Name-hint" <?= isset($errors[$name]) ? 'aria-invalid="true"' : '' ?>></div>
    <?php elseif ($type === 'select'): ?>
    <select id="<?= e($name) ?>" name="<?= e($name) ?>" <?= $required ? 'required' : '' ?> <?= isset($errors[$name]) ? 'aria-invalid="true"' : '' ?> aria-describedby="<?= e(implode(' ', $descriptions)) ?>">
        <option value="">Select <?= e(strtolower($field['label'])) ?></option>
        <?php foreach ($field['options'] as $key => $label): ?><option value="<?= e($key) ?>"<?= selected($value, $key) ?>><?= e($label) ?></option><?php endforeach; ?>
    </select>
    <?php elseif ($type === 'textarea'): ?>
    <textarea id="<?= e($name) ?>" name="<?= e($name) ?>" rows="4" maxlength="<?= e($field['max'] ?? 5000) ?>" <?= $required ? 'required' : '' ?> <?= isset($errors[$name]) ? 'aria-invalid="true"' : '' ?> aria-describedby="<?= e(implode(' ', $descriptions)) ?>"><?= e($value) ?></textarea>
    <?php else: ?>
    <input id="<?= e($name) ?>" name="<?= e($name) ?>" type="<?= e($type) ?>" <?= isset($field['suggestions']) ? 'list="' . e($name) . '-suggestions"' : '' ?> value="<?= e($value) ?>" <?= $required ? 'required' : '' ?> <?= isset($errors[$name]) ? 'aria-invalid="true"' : '' ?> aria-describedby="<?= e(implode(' ', $descriptions)) ?>"<?php foreach (['min', 'max', 'step', 'placeholder', 'autocomplete'] as $attribute): if (isset($field[$attribute]) && !($attribute === 'max' && $type !== 'number')): ?> <?= $attribute ?>="<?= e($field[$attribute]) ?>"<?php endif; endforeach; ?><?php if (isset($field['max']) && in_array($type, ['text', 'email', 'tel'], true)): ?> maxlength="<?= e($field['max']) ?>"<?php endif; ?>>
    <?php endif; ?>
    <?php if (isset($field['suggestions'])): ?><datalist id="<?= e($name) ?>-suggestions"><?php foreach ($field['suggestions'] as $suggestion): ?><option value="<?= e($suggestion) ?>"></option><?php endforeach; ?></datalist><?php endif; ?>
    <?php if (isset($field['hint'])): ?><small id="<?= e($name) ?>-hint"><?= e($field['hint']) ?></small><?php endif; ?>
    <?php if (isset($field['link'])): ?><a class="field-link" href="<?= e(url($field['link'][0])) ?>"><?= e($field['link'][1]) ?> ↗</a><?php endif; ?>
    <?php if (isset($errors[$name])): ?><small class="field-error" id="<?= e($name) ?>-error"><?= e($errors[$name]) ?></small><?php endif; ?>
</div>
<?php endforeach; ?>
</div>
