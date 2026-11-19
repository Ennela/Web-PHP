<?php
/**
 * sql_helper.php — Hàm hỗ trợ phòng chống SQL Injection (dùng chung cho admin/ và shop/).
 *
 * Nguyên tắc:
 *  1. Ưu tiên Prepared Statements (mysqli_prepare + bind_param) cho mọi dữ liệu người dùng.
 *  2. Với các phần KHÔNG thể bind (tên cột, LIMIT/OFFSET, danh sách IN động):
 *     ép kiểu số nguyên hoặc đối chiếu với whitelist.
 *  3. Không bao giờ nối trực tiếp $_GET / $_POST / $_COOKIE vào câu SQL.
 */

if (!function_exists('sql_int')) {

    /** Ép kiểu số nguyên an toàn. Giá trị không hợp lệ -> $default. */
    function sql_int($value, $default = 0)
    {
        if (is_int($value)) {
            return $value;
        }
        if (is_scalar($value) && preg_match('/^-?\d+$/', trim((string)$value))) {
            return (int)$value;
        }
        return (int)$default;
    }

    /** Escape chuỗi (chỉ dùng khi không thể dùng prepared statement). */
    function sql_esc($con, $value)
    {
        return mysqli_real_escape_string($con, is_scalar($value) ? (string)$value : '');
    }

    /** Chuyển mảng giá trị thành chuỗi "1,2,3" gồm toàn số nguyên dương -> dùng cho IN (...). */
    function sql_int_list($values)
    {
        $out = [];
        foreach ((array)$values as $v) {
            $n = sql_int($v, 0);
            if ($n > 0) {
                $out[$n] = $n;
            }
        }
        return $out ? implode(',', $out) : '0';
    }

    /**
     * Phân trang an toàn: trả về [per_page, current_page, offset] đều là số nguyên hợp lệ.
     */
    function sql_pagination($perPageRaw, $pageRaw, $defaultPer = 6, $maxPer = 100)
    {
        $per = sql_int($perPageRaw, $defaultPer);
        if ($per < 1)       { $per = $defaultPer; }
        if ($per > $maxPer) { $per = $maxPer; }
        $page = sql_int($pageRaw, 1);
        if ($page < 1) { $page = 1; }
        return [$per, $page, ($page - 1) * $per];
    }

    /**
     * Dựng điều kiện WHERE an toàn từ bộ lọc tìm kiếm (thường là $_POST).
     *  - Chỉ chấp nhận tên trường là CỘT THẬT của bảng (whitelist lấy từ SHOW COLUMNS).
     *  - Giá trị luôn được escape và đặt trong dấu nháy; cột trong $likeFields dùng LIKE.
     *
     * @return array [string $where, array $clean]  ($clean = bộ lọc đã lọc, dùng để điền lại form)
     */
    function sql_build_filter($con, $table, $filters, $likeFields = [])
    {
        $columns = [];
        $res = mysqli_query($con, 'SHOW COLUMNS FROM `' . str_replace('`', '', $table) . '`');
        if ($res) {
            while ($c = mysqli_fetch_assoc($res)) {
                $columns[$c['Field']] = true;
            }
        }

        $parts = [];
        $clean = [];
        foreach ((array)$filters as $field => $value) {
            if (!is_string($field) || !isset($columns[$field]) || !is_scalar($value)) {
                continue;                                   // bỏ qua trường lạ / giá trị là mảng
            }
            $value = trim((string)$value);
            if ($value === '') {
                continue;
            }
            $clean[$field] = $value;
            $esc = mysqli_real_escape_string($con, $value);
            if (in_array($field, $likeFields, true)) {
                $esc = addcslashes($esc, '%_');
                $parts[] = "`$field` LIKE '%$esc%'";
            } else {
                $parts[] = "`$field` = '$esc'";
            }
        }
        return [implode(' AND ', $parts), $clean];
    }
}
