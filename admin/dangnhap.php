<?php
/**
 * Trang đăng nhập admin dự phòng (cũ).
 *
 * Để tránh tồn tại 2 luồng đăng nhập với mức bảo vệ khác nhau
 * (Broken Authentication), mọi yêu cầu được chuyển về trang đăng nhập
 * duy nhất đã được gia cố: canhbao.php
 * (giới hạn số lần sai, chống session fixation, vòng đời phiên).
 */
require_once dirname(__DIR__) . '/config.php';

header('Location: canhbao.php', true, 303);
exit;
