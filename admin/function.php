<?php
require_once dirname(__DIR__) . '/config.php';
require_once BASE_PATH . 'includes/upload_helper.php';

/** Compatibility wrapper for callers uploading one image or an image list. */
function uploadFiles($uploadedFiles) {
    if (!is_array($uploadedFiles) || !isset($uploadedFiles['name'])) {
        return ['errors' => 'Dữ liệu ảnh tải lên không hợp lệ.'];
    }
    if (!is_array($uploadedFiles['name'])) {
        $result = processUploadFile($uploadedFiles, __DIR__ . '/uploads');
        return $result['error'] ? ['errors' => $result['message']] : ['path' => $result['path']];
    }

    $validated = [];
    $paths = [];
    try {
        foreach ($uploadedFiles['name'] as $index => $name) {
            $file = [];
            foreach (['name', 'tmp_name', 'size', 'error'] as $key) {
                $file[$key] = $uploadedFiles[$key][$index] ?? null;
            }
            $validated[] = app_validate_image_upload($file);
        }
        foreach ($validated as $image) {
            $paths[] = app_store_validated_image($image, __DIR__ . '/uploads', 'uploads');
        }
        return ['errors' => [], 'uploaded_files' => $paths];
    } catch (InvalidArgumentException | RuntimeException $error) {
        foreach ($paths as $path) {
            @unlink(__DIR__ . '/' . $path);
        }
        return ['errors' => [$error->getMessage()], 'uploaded_files' => []];
    }
}

function processUploadFile($file, $uploadPath) {
    try {
        if (!is_array($file)) {
            throw new InvalidArgumentException('Dữ liệu ảnh tải lên không hợp lệ.');
        }
        $path = app_store_image_upload($file, $uploadPath, 'uploads');
        return ['error' => false, 'path' => $path];
    } catch (InvalidArgumentException | RuntimeException $error) {
        return ['error' => true, 'message' => $error->getMessage()];
    }
}

function validateUploadFile($file, $uploadPath) {
    try {
        if (!is_array($file)) {
            return false;
        }
        return array_merge($file, app_validate_image_upload($file));
    } catch (InvalidArgumentException | RuntimeException $error) {
        return false;
    }
}

    function validateDateTime($date) {
        //Kiểm tra định dạng ngày tháng xem đúng DD/MM/YYYY hay chưa?
        preg_match('/^[0-9]{1,2}-[0-9]{1,2}-[0-9]{4}$/', $date, $matches);
        if (count($matches) == 0) { //Nếu ngày tháng nhập không đúng định dạng thì $match = array(); (rỗng)
            return false;
        }
        $separateDate = explode('-', $date);
        $day = (int) $separateDate[0];
        $month = (int) $separateDate[1];
        $year = (int) $separateDate[2];
        //Nếu là tháng 2
        if ($month == 2) {
            if ($year % 4 == 0) { //Nếu là năm nhuận
                if ($day > 29) {
                    return false;
                }
            } else { //Không phải năm nhuận
                if ($day > 28) {
                    return false;
                }
            }
        }
        //Check các tháng khác
        switch ($month) {
            case 1:
            case 3:
            case 5:
            case 7:
            case 8:
            case 10:
            case 12:
                if ($day > 31) {
                    return false;
                }
                break;
            case 4:
            case 6:
            case 9:
            case 11:
                if ($day > 30) {
                    return false;
                }
                break;
        }
        return true;
    }

?>