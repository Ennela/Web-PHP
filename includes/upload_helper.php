<?php
/** Accept raster images only, independently of the browser's Content-Type. */
function app_validate_image_upload(array $file, int $maxBytes = 5242880): array
{
    foreach (['name', 'tmp_name', 'error', 'size'] as $key) {
        if (!array_key_exists($key, $file) || is_array($file[$key])) {
            throw new InvalidArgumentException('Dữ liệu ảnh tải lên không hợp lệ.');
        }
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new InvalidArgumentException('Không thể nhận ảnh tải lên. Vui lòng kiểm tra dung lượng ảnh.');
    }
    if (!is_string($file['name']) || !is_string($file['tmp_name'])
        || !is_uploaded_file($file['tmp_name'])) {
        throw new InvalidArgumentException('Dữ liệu ảnh tải lên không hợp lệ.');
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $extensions = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
        'webp' => 'image/webp', 'gif' => 'image/gif'];
    if (!isset($extensions[$extension])
        || preg_match('/[\x00-\x1f\x7f]/', $file['name'])
        || preg_match('/\.(?:php\d*|phtml|phar)(?:\.|$)/i', $file['name'])) {
        throw new InvalidArgumentException('Chỉ chấp nhận ảnh JPG, JPEG, PNG, WebP hoặc GIF.');
    }

    $actualSize = filesize($file['tmp_name']);
    if ($actualSize === false || $actualSize < 1 || $actualSize > $maxBytes) {
        throw new InvalidArgumentException('Dung lượng ảnh vượt quá giới hạn cho phép.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $image = @getimagesize($file['tmp_name']);
    if ($mime !== $extensions[$extension] || $image === false
        || ($image['mime'] ?? '') !== $mime || $image[0] <= 0 || $image[1] <= 0) {
        throw new InvalidArgumentException('Nội dung ảnh không hợp lệ hoặc không khớp định dạng file.');
    }
    // Names are generated here: uploaded filenames never become server paths.
    $canonicalExtension = $mime === 'image/jpeg' ? 'jpg' : $extension;
    return ['tmp_name' => $file['tmp_name'], 'name' => bin2hex(random_bytes(16)) . '.' . $canonicalExtension];
}

function app_store_validated_image(array $image, string $uploadDir, string $relativeDir): string
{
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
        throw new RuntimeException('Không thể lưu ảnh. Vui lòng thử lại sau.');
    }
    $destination = rtrim($uploadDir, '/\\') . DIRECTORY_SEPARATOR . $image['name'];
    if (!move_uploaded_file($image['tmp_name'], $destination)) {
        throw new RuntimeException('Không thể lưu ảnh. Vui lòng thử lại sau.');
    }
    chmod($destination, 0644);
    return rtrim($relativeDir, '/') . '/' . $image['name'];
}

function app_store_image_upload(array $file, string $uploadDir, string $relativeDir, int $maxBytes = 5242880): string
{
    return app_store_validated_image(app_validate_image_upload($file, $maxBytes), $uploadDir, $relativeDir);
}

/** Validate every image first so an invalid secondary image cannot be saved. */
function app_upload_admin_images(array $files, bool $requirePrimary = false): array
{
    $validated = [];
    foreach (['anhdaidien', 'anhgiuoithieu1', 'anhgiuoithieu2'] as $field) {
        $file = $files[$field] ?? null;
        if ($file === null || (is_array($file) && ($file['error'] ?? null) === UPLOAD_ERR_NO_FILE)) {
            if ($requirePrimary && $field === 'anhdaidien') {
                throw new InvalidArgumentException('Vui lòng chọn ảnh đại diện.');
            }
            continue;
        }
        if (!is_array($file)) {
            throw new InvalidArgumentException('Dữ liệu ảnh tải lên không hợp lệ.');
        }
        $validated[$field] = app_validate_image_upload($file);
    }

    $paths = [];
    try {
        foreach ($validated as $field => $image) {
            $paths[$field] = app_store_validated_image($image, BASE_PATH . 'admin/uploads', 'uploads');
        }
    } catch (Throwable $error) {
        foreach ($paths as $path) {
            @unlink(BASE_PATH . 'admin/' . $path);
        }
        throw $error;
    }
    return $paths;
}
