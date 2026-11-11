<?php
declare(strict_types=1);

namespace NVRO\Services;

use NVRO\Validation\ValidationException;

final class VehiclePhotos
{
    public const COLUMNS = ['Image_Path', 'Image_Path_2', 'Image_Path_3'];

    public static function paths(array $vehicle): array
    {
        $paths = [];
        foreach (self::COLUMNS as $column) {
            $path = $vehicle[$column] ?? '';
            if (preg_match('~^(?:uploads/vehicles/|assets/)[a-zA-Z0-9_./-]+\.(?:jpg|jpeg|png|webp)$~i', $path)
                && strpos($path, '..') === false && is_file(dirname(__DIR__, 2) . '/' . $path)) {
                $paths[] = $path;
            }
        }
        return $paths;
    }

    public function upload(array $files): array
    {
        if (!$files) { return []; }
        if (!isset($files['error']) || !is_array($files['error']) || count($files['error']) > 3) {
            throw new ValidationException(['Photos' => 'Select up to three vehicle photos.']);
        }
        $validated = [];
        $types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        foreach ($files['error'] as $key => $error) {
            if ($error === UPLOAD_ERR_NO_FILE) { continue; }
            $tmp = $files['tmp_name'][$key] ?? null;
            if ($error !== UPLOAD_ERR_OK || !is_string($tmp) || !is_uploaded_file($tmp)) {
                throw new ValidationException(['Photos' => 'A photo could not be uploaded. Please select the photos again.']);
            }
            if (filesize($tmp) > 5 * 1024 * 1024) {
                throw new ValidationException(['Photos' => 'Each photo must be 5 MB or smaller.']);
            }
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($tmp);
            $info = @getimagesize($tmp);
            if (!isset($types[$mime]) || !$info || ($info['mime'] ?? '') !== $mime) {
                throw new ValidationException(['Photos' => 'Use JPG, PNG, or WebP images.']);
            }
            $validated[] = [$tmp, $types[$mime]];
        }
        $paths = [];
        try {
            foreach ($validated as [$tmp, $extension]) {
                $path = 'uploads/vehicles/' . bin2hex(random_bytes(16)) . '.' . $extension;
                if (!move_uploaded_file($tmp, dirname(__DIR__, 2) . '/' . $path)) {
                    throw new \RuntimeException('Could not store the vehicle photo.');
                }
                $paths[] = $path;
            }
        } catch (\Throwable $exception) {
            self::remove($paths);
            throw $exception;
        }
        return $paths;
    }

    public static function remove(array $paths): void
    {
        foreach ($paths as $path) {
            if (preg_match('~^uploads/vehicles/[a-f0-9]{32}\.(jpg|png|webp)$~', $path)) {
                $file = dirname(__DIR__, 2) . '/' . $path;
                if (is_file($file) && !unlink($file)) { error_log('Could not remove vehicle photo: ' . $path); }
            }
        }
    }
}
