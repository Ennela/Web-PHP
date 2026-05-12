<?php

session_start();
date_default_timezone_set('Asia/Ho_Chi_Minh');
if (!isset($_SESSION['dangnhap1'])) {
    header('Location: canhbao.php');
    exit;
}
if (isset($_GET['login'])) {
    $dangxuat = $_GET['login'];
} else {
    $dangxuat = '';
}
if ($dangxuat == 'dangxuat') {
    session_destroy();
    header('Location: index.php');
}
include './connect_db.php';
require_once dirname(__DIR__) . '/includes/inventory_helper.php';

// ===== XỬ LÝ CẬP NHẬT TRẠNG THÁI =====
if (!empty($_POST['action']) && $_POST['action'] == 'update_status') {
    $order_id = intval($_POST['order_id']);
    $new_status = mysqli_real_escape_string($con, $_POST['new_status']);
    $valid_statuses = ['PENDING', 'CONFIRMED', 'SHIPPING', 'DELIVERED', 'CANCELLED'];
    
    if (in_array($new_status, $valid_statuses) && $order_id > 0) {
        // Lấy trạng thái hiện tại của đơn hàng
        $currentOrderResult = mysqli_query($con, "SELECT `status`, `payment_status` FROM `oder` WHERE `id` = $order_id LIMIT 1");
        $currentOrder = mysqli_fetch_assoc($currentOrderResult);
        $oldStatus = $currentOrder['status'] ?? '';
        $paymentStatus = $currentOrder['payment_status'] ?? 'UNPAID';
        
        // Nếu chuyển sang CANCELLED → hoàn tồn kho
        if ($new_status === 'CANCELLED' && $oldStatus !== 'CANCELLED') {
            restoreStock($con, $order_id);
            $paymentUpdate = ($paymentStatus === 'PAID') ? ", `payment_status` = 'REFUNDED'" : "";
            mysqli_query($con, "UPDATE `oder` SET `status` = '$new_status' $paymentUpdate WHERE `id` = $order_id");
        }
        // Nếu chuyển từ CANCELLED sang trạng thái khác → kiểm tra và trừ lại tồn kho
        elseif ($oldStatus === 'CANCELLED' && $new_status !== 'CANCELLED') {
            // Lấy danh sách sản phẩm trong đơn
            $detailResult = mysqli_query($con, "SELECT `masp`, `size`, `quantity` FROM `oder_chitiet` WHERE `madonhang` = $order_id");
            $items = [];
            while ($d = mysqli_fetch_assoc($detailResult)) {
                $items[] = ['masp' => $d['masp'], 'size' => $d['size'], 'quantity' => $d['quantity']];
            }
            $stockResult = validateAndDeductStock($con, $items);
            if ($stockResult['success']) {
                $paymentUpdate = ($new_status === 'DELIVERED') ? ", `payment_status` = 'PAID'" : "";
                mysqli_query($con, "UPDATE `oder` SET `status` = '$new_status' $paymentUpdate WHERE `id` = $order_id");
            } else {
                // Không đủ tồn kho, không cho chuyển trạng thái
                $errorMsgs = array_map(function($e) { return $e['message']; }, $stockResult['errors']);
                $_SESSION['admin_error'] = 'Không đủ tồn kho để khôi phục đơn hàng: ' . implode(', ', $errorMsgs);
            }
        }
        // Chuyển trạng thái bình thường
        else {
            $paymentUpdate = ($new_status === 'DELIVERED' && $paymentStatus === 'UNPAID') ? ", `payment_status` = 'PAID'" : "";
            mysqli_query($con, "UPDATE `oder` SET `status` = '$new_status' $paymentUpdate WHERE `id` = $order_id");
        }
    }
    header('Location: quanlidonhang.php' . (!empty($_GET['page']) ? '?page=' . $_GET['page'] : ''));
    exit;
}

if (
    !empty($_GET['action']) && $_GET['action'] == 'search'
    && !empty($_POST)
) {
    $_SESSION['locsanpham2'] = $_POST;
    header('Location: quanlidonhang.php');
    exit;
}
if (!empty($_SESSION['locsanpham2'])) {
    $where = "";
    foreach ($_SESSION['locsanpham2'] as $field => $value) {
        if (!empty($value)) {
            switch ($field) {
                case 'tenkh':
                    $where .= (!empty($where)) ? " AND " . "`" . $field . "` LIKE '%" . $value . "%'" : "`" . $field . "` LIKE '%" . $value . "%'";
                    break;
                case 'status':
                    $where .= (!empty($where)) ? " AND `status` = '" . mysqli_real_escape_string($con, $value) . "'" : "`status` = '" . mysqli_real_escape_string($con, $value) . "'";
                    break;
                default:
                    $where .= (!empty($where)) ? " AND " . "`" . $field . "` = " . $value . "" : "`" . $field . "` = " . $value . "";
                    break;

            }
        }
    }
    extract($_SESSION['locsanpham2']);
}

$item_per_page = !empty($_GET['per_page']) ? $_GET['per_page'] : 6;
$current_page = !empty($_GET['page']) ? $_GET['page']
    : 1;
$offset = ($current_page - 1) * $item_per_page;
$totalRecords = mysqli_query($con, "SELECT * FROM `oder`");
$totalRecords = $totalRecords->num_rows;

$totalPages = ceil($totalRecords / $item_per_page);
if (!empty($where)) {
    $result = mysqli_query(
        $con,
        "SELECT * FROM `oder` where (" . $where . ") ORDER BY `id` DESC  LIMIT "
        . $item_per_page . " OFFSET " . $offset
    );
} else {
    $result = mysqli_query(
        $con,
        "SELECT * FROM `oder` ORDER BY `id` DESC  LIMIT "
        . $item_per_page . " OFFSET " . $offset
    );
}

// Status map for display
function getAdminStatusInfo($status) {
    $map = [
        'PENDING'    => ['label' => 'Chờ xử lý',      'color' => 'yellow', 'icon' => 'fa-clock'],
        'CONFIRMED'  => ['label' => 'Đã xác nhận',    'color' => 'blue',   'icon' => 'fa-check-circle'],
        'SHIPPING'   => ['label' => 'Đang giao hàng', 'color' => 'purple', 'icon' => 'fa-truck'],
        'DELIVERED'  => ['label' => 'Đã giao hàng',   'color' => 'green',  'icon' => 'fa-box-open'],
        'CANCELLED'  => ['label' => 'Đã hủy',         'color' => 'red',    'icon' => 'fa-times-circle'],
    ];
    return $map[strtoupper($status)] ?? $map['PENDING'];
}


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="ie=edge" http-equiv="X-UA-Compatible">
    <title>Quản lí đơn hàng</title>
    <link href="https://use.fontawesome.com/releases/v5.3.1/css/all.css" rel="stylesheet">
    <link href="https://unpkg.com/tailwindcss@^2/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://afeld.github.io/emoji-css/emoji.css" rel="stylesheet">
    <link href="css/test.css" rel="stylesheet">
    <style>
        /* Status badge styles */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.3px;
            white-space: nowrap;
        }
        .status-yellow  { background: #fef3c7; color: #92400e; }
        .status-blue    { background: #dbeafe; color: #1e40af; }
        .status-purple  { background: #ede9fe; color: #5b21b6; }
        .status-green   { background: #d1fae5; color: #065f46; }
        .status-red     { background: #fee2e2; color: #991b1b; }

        /* Status select */
        .status-select {
            padding: 6px 8px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            background: #fff;
            transition: all 0.2s ease;
            outline: none;
            min-width: 140px;
        }
        .status-select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }
        .status-select:hover {
            border-color: #94a3b8;
        }

        /* Update button */
        .btn-update {
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.78rem;
            font-weight: 700;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .btn-update-save {
            background: #3b82f6;
            color: #fff;
        }
        .btn-update-save:hover {
            background: #2563eb;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        /* Total card */
        .total-card {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 20px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.85rem;
        }

        /* Table improvements */
        #table_customers th {
            text-transform: uppercase;
            font-size: 0.72rem;
            letter-spacing: 0.5px;
        }
        #table_customers td {
            font-size: 0.85rem;
            vertical-align: middle;
        }
        #table_customers tr:hover {
            background-color: #f0f9ff !important;
        }

        /* Status form inline */
        .status-form {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* Toast notification */
        .toast-success {
            position: fixed;
            top: 60px;
            right: 20px;
            background: #10b981;
            color: #fff;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.9rem;
            z-index: 9999;
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.3);
            animation: slideIn 0.3s ease, fadeOut 0.5s ease 2.5s forwards;
        }
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes fadeOut {
            to { opacity: 0; transform: translateX(50px); }
        }

        /* Filter status tabs */
        .filter-pills {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }
        .filter-pill {
            padding: 5px 14px;
            border-radius: 50px;
            font-size: 0.78rem;
            font-weight: 600;
            text-decoration: none;
            border: 2px solid #e2e8f0;
            color: #64748b;
            transition: all 0.2s;
            cursor: pointer;
        }
        .filter-pill:hover {
            border-color: #3b82f6;
            color: #3b82f6;
            text-decoration: none;
        }
        /* Order detail expand row */
        .detail-row {
            display: none;
        }
        .detail-row.open {
            display: table-row;
        }
        .detail-row td {
            padding: 0 !important;
            border-top: none !important;
            border-bottom: 2px solid #cbd5e1 !important;
            background: #f8fafc !important;
        }
        .detail-content {
            padding: 16px 20px;
        }
        .detail-product-table {
            width: 100%;
            font-size: 0.82rem;
            border-collapse: collapse;
        }
        .detail-product-table th {
            background: #e2e8f0;
            padding: 6px 10px;
            text-align: left;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #475569;
        }
        .detail-product-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #f1f5f9;
        }
        .size-tag {
            background: #1e293b;
            color: #fff;
            padding: 2px 8px;
            border-radius: 4px;
            font-weight: 700;
            font-size: 0.75rem;
        }
        .btn-detail {
            background: none;
            border: 1px solid #94a3b8;
            border-radius: 6px;
            padding: 4px 10px;
            font-size: 0.75rem;
            font-weight: 600;
            color: #475569;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-detail:hover {
            background: #e2e8f0;
            border-color: #64748b;
        }
        .btn-detail.active {
            background: #1e293b;
            color: #fff;
            border-color: #1e293b;
        }

        /* ===== ORDER MANAGEMENT RESPONSIVE ===== */
        @media (max-width: 768px) {
            /* Search form wrap */
            .w-full.p-4 .flex {
                flex-wrap: wrap;
                gap: 8px;
            }
            .w-full.p-4 input[type="text"],
            .w-full.p-4 select {
                width: 100% !important;
                margin: 0 !important;
            }
            .w-full.p-4 button[type="submit"] {
                width: 100%;
            }

            /* Status form stack */
            .status-form {
                flex-direction: column;
                gap: 4px;
            }
            .status-select {
                min-width: 100%;
                font-size: 0.75rem;
            }

            /* Table scroll */
            .w-full.p-6 {
                padding: 0.5rem !important;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            #table_customers {
                min-width: 900px;
            }

            /* Detail content */
            .detail-content {
                padding: 10px;
            }
            .detail-product-table {
                font-size: 0.75rem;
            }
            .detail-product-table th,
            .detail-product-table td {
                padding: 4px 6px;
            }

            /* Total info */
            .flex.w-full.mr-8 {
                padding: 0 1rem;
                font-size: 0.85rem;
            }
        }

        @media (max-width: 576px) {
            .status-select {
                font-size: 0.7rem;
                padding: 4px 6px;
            }
            .btn-detail {
                font-size: 0.68rem;
                padding: 3px 8px;
            }
            .total-card {
                flex-direction: column;
                text-align: center;
                padding: 8px 12px;
            }
        }
    </style>
</head>


<body class="bg-gray-800 font-sans leading-normal tracking-normal mt-12">

    <!--Nav-->
    <nav class="fixed top-0 z-20 px-1 pt-2 pb-1 mt-0 w-full h-auto bg-gray-800 md:pt-1">

        <div class="flex flex-wrap items-center">
            <div class="flex flex-shrink justify-center text-white md:w-1/3 md:justify-start">
                <a href="#">
                    <span class="pl-2 text-xl"><i class="em em-grinning"></i></span>
                </a>
            </div>

            <div class="flex flex-1 justify-center px-2 text-white md:w-1/3 md:justify-start">
                <span class="relative w-full">
                    <input
                        class="px-2 py-3 pl-10 w-full leading-normal text-white bg-gray-900 rounded border border-transparent transition appearance-none focus:outline-none focus:border-gray-400"
                        placeholder="Tìm kiếm" type="search">
                    <div class="absolute search-icon" style="top: 1rem; left: .8rem;">
                        <svg class="w-4 h-4 text-white pointer-events-none fill-current" viewBox="0 0 20 20"
                            xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M12.9 14.32a8 8 0 1 1 1.41-1.41l5.35 5.33-1.42 1.42-5.33-5.34zM8 14A6 6 0 1 0 8 2a6 6 0 0 0 0 12z">
                            </path>
                        </svg>
                    </div>
                </span>
            </div>

            <div class="flex justify-between content-center pt-2 w-full md:w-1/3 md:justify-end">
                <ul class="flex flex-1 justify-between items-center list-reset md:flex-none">

                    <li class="flex-1 md:flex-none md:mr-3">
                        <div class="inline-block relative">
                            <button class="text-white drop-button focus:outline-none"
                                onclick="toggleDD('myDropdown')"><span class="pr-2"><i
                                        class="em em-robot_face"></i></span>
                                Xin chào, <?php
                                echo $_SESSION['dangnhap1'] ?>
                                <svg class="inline h-3 fill-current" viewBox="0 0 20 20"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z" />
                                </svg>
                            </button>
                            <div class="overflow-auto absolute right-0 invisible z-30 p-3 mt-3 text-white bg-gray-800 dropdownlist"
                                id="myDropdown">

                                <a class="block p-2 text-sm text-white no-underline hover:bg-blue-800 hover:no-underline"
                                    href="dangnhap.php" style="width: 120px;"><i class="fa fa-user fa-fw"></i> Đăng nhập
                                </a>
                                <div class="border border-gray-800"></div>
                                <a class="block p-2 text-sm text-white no-underline hover:bg-blue-800 hover:no-underline"
                                    href="?login=dangxuat" style="width: 120px;"><i
                                        class="fas fa-sign-out-alt fa-fw"></i>
                                    Đăng xuất</a>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>

    </nav>


    <div class="flex flex-col md:flex-row">

        <div class="bg-gray-800 shadow-xl h-16 fixed bottom-0 mt-12 md:relative md:h-screen z-10 w-full md:w-48">

            <div
                class="md:mt-12 md:w-50 md:fixed md:left-0 md:top-0 content-center md:content-start text-left justify-between">
                <ul class="list-reset flex flex-row md:flex-col py-0 md:py-3 px-1 md:px-2 text-center md:text-left">
                    <li class="mr-3 flex-1">
                        <a class="block py-1 md:py-3 pl-1 align-middle text-white no-underline hover:text-white border-b-2 border-gray-800 hover:border-pink-500"
                            href="./trangchu.php">
                            <i class="fas fa-tasks pr-0 md:pr-3"></i><span
                                class="pb-1 md:pb-0 text-xs md:text-base text-gray-600 md:text-gray-400 block md:inline-block">Trang
                                chủ</span>
                        </a>
                    </li>

                    <li class="mr-3 flex-1">
                        <a class="block py-1 md:py-3 pl-1 align-middle text-white no-underline hover:text-white border-b-2 border-gray-800 hover:border-blue-700"
                            href="./quanlisanpham.php">

                            <i class="fas fa-dolly md:pr-3"></i>
                            <span
                                class="pb-1 md:pb-0 text-xs md:text-base text-gray-600 md:text-gray-400 block md:inline-block">Quản
                                lí sản phẩm</span>
                        </a>
                    </li>

                    <li class="mr-3 flex-1">
                        <a class="block py-1 md:py-3 pl-1 align-middle text-white no-underline hover:text-white border-b-2 border-gray-800 hover:border-yellow-400"
                            href="./quanlikhachhang.php">

                            <i class="far fa-address-card md:pr-3"></i>
                            <span
                                class="pb-1 md:pb-0 text-xs md:text-base text-gray-600 md:text-gray-400 block md:inline-block">Quản
                                lí Khách hàng</span>
                        </a>
                    </li>
                    <li class="mr-3 flex-1">
                        <a class="block py-1 md:py-3 pl-1 align-middle text-white no-underline hover:text-white border-b-2 border-gray-800 hover:border-blue-500"
                            href="./quanlibaidang.php">

                            <i class="fas fa-align-left md:pr-3"></i>
                            <span
                                class="pb-1 md:pb-0 text-xs md:text-base text-gray-600 md:text-gray-400 block md:inline-block">Quản
                                lí bài đăng</span>
                        </a>
                    </li>

                    <li class="mr-3 flex-1">
                        <a class="block py-1 md:py-3 pl-0 md:pl-1 align-middle text-white no-underline hover:text-white border-b-2 border-gray-800 hover:border-pink-400"
                            href="./quanlithanhvien.php">
                            <i class="far fa-address-book md:pr-3"></i>

                            <span
                                class="pb-1 md:pb-0 text-xs md:text-base text-gray-600 md:text-gray-400 block md:inline-block">Quản
                                lí thành viên</span>
                        </a>
                    </li>
                    <li class="mr-3 flex-1">
                        <a class="block py-1 md:py-3 pl-0 md:pl-1 align-middle text-white no-underline hover:text-white border-b-2 border-gray-800 border-green-400"
                            href="./quanlidonhang.php">
                            <i class="fas fa-wallet md:pr-3"></i>


                            <span
                                class="pb-1 md:pb-0 text-xs md:text-base text-gray-600 md:text-gray-400 block md:inline-block">Quản
                                lí đơn hàng</span>
                        </a>
                    </li>

                </ul>
            </div>


        </div>

        <div class="main-content flex-1 bg-gray-100 mt-12 md:mt-2 pb-24 md:pb-5">

            <div class="bg-gray-800 pt-3">
                <div class="rounded-tl-3xl bg-gradient-to-r from-blue-900 to-gray-800 p-4 shadow text-2xl text-white">
                    <h3 class="font-bold pl-2">Quản lí đơn hàng</h3>
                </div>
            </div>


            <div class="flex flex-wrap">
                <div class="w-full p-6">
                    <!--Metric Card-->
                    <?php if (!empty($_SESSION['admin_error'])): ?>
                    <div style="background:#fee2e2; border:1px solid #fecaca; border-radius:10px; padding:12px 16px; margin-bottom:16px; color:#991b1b; font-weight:600; font-size:0.88rem;">
                        <i class="fas fa-exclamation-triangle" style="margin-right:6px;"></i> <?= htmlspecialchars($_SESSION['admin_error']) ?>
                    </div>
                    <?php unset($_SESSION['admin_error']); endif; ?>
                    <div
                        class="bg-gradient-to-b from-indigo-200 to-indigo-100 border-b-4 border-indigo-500 rounded-lg shadow-xl p-5">
                        <div class="flex flex-row items-center">
                            <div class="flex-shrink pr-4">
                                <div class="rounded-full p-5 bg-indigo-600"><i
                                        class="fas fa-tasks fa-2x fa-inverse"></i>
                                </div>
                            </div>
                            <div class="flex-1 text-right md:text-center">
                                <h5 class="font-bold uppercase text-gray-600">Số đơn
                                    hàng</h5>
                                <h3 class="font-bold text-3xl"><?= $totalRecords ?> đơn</h3>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="w-full p-4 flex flex-row-reverse">


                    <div class="mr-8 flex items-center justify-center ">
                        <form action="quanlidonhang.php?action=search" method="POST">
                            <div class="flex ">
                                <input type="text" class="border-2 border-gray-200 rounded px-2 py-2 w-30"
                                    placeholder="Nhập id ..." name="id" value="<?= !empty($id) ? $id : "" ?>" />
                                <input type="text" class="border-2 border-gray-200 rounded mr-2 px-2 ml-2 py-2 w-30"
                                    placeholder="Nhập tên khách hàng ..." name="tenkh"
                                    value="<?= !empty($tenkh) ? $tenkh : "" ?>" />
                                <select name="status" class="border-2 border-gray-200 rounded px-2 py-2 mr-2 w-40" style="font-size:0.85rem;">
                                    <option value="">-- Trạng thái --</option>
                                    <option value="PENDING" <?= (!empty($status) && $status == 'PENDING') ? 'selected' : '' ?>>Chờ xử lý</option>
                                    <option value="CONFIRMED" <?= (!empty($status) && $status == 'CONFIRMED') ? 'selected' : '' ?>>Đã xác nhận</option>
                                    <option value="SHIPPING" <?= (!empty($status) && $status == 'SHIPPING') ? 'selected' : '' ?>>Đang giao hàng</option>
                                    <option value="DELIVERED" <?= (!empty($status) && $status == 'DELIVERED') ? 'selected' : '' ?>>Đã giao hàng</option>
                                    <option value="CANCELLED" <?= (!empty($status) && $status == 'CANCELLED') ? 'selected' : '' ?>>Đã hủy</option>
                                </select>
                                <button type="submit" value="Tìm"
                                    class=" rounded px-4 mr-2 text-white bg-blue-500 border-l  ">
                                    Tìm
                                </button>
                            </div>
                        </form>

                    </div>

                </div>
                <div class="flex w-full mr-8 justify-end">
                    <div>Có tất cả <strong><?= $totalRecords ?></strong> đơn hàng trên
                        <strong><?= $totalPages ?></strong>
                        trang</div>
                </div>
                <div class="w-full p-6">
                    <table id="table_customers">
                        <thead class="bg-blue-300">
                            <tr class="border-2">
                                <th class="w-1/12">Mã đơn</th>
                                <th class="w-2/12">Tên khách hàng</th>
                                <th class="w-1/12">Điện thoại</th>
                                <th class="w-2/12">Địa chỉ</th>
                                <th class="w-1/12">Tổng tiền</th>
                                <th class="w-1/12">Ngày tạo</th>
                                <th class="w-1/12">Trạng thái</th>
                                <th class="w-1/12">Cập nhật trạng thái</th>
                                <th class="w-1/12">Chi tiết</th>
                                <th class="w-1/12">In đơn</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            while ($row = mysqli_fetch_array($result)) {
                                $statusInfo = getAdminStatusInfo($row['status'] ?? 'PENDING');
                                ?>
                                <tr>
                                    <td class="text-center font-bold" style="color:#3b82f6;"><?= !empty($row['order_code']) ? $row['order_code'] : '#' . $row['id'] ?></td>
                                    <td><?= htmlspecialchars($row['tenkh']) ?></td>
                                    <td class="text-center"><?= htmlspecialchars($row['sdt']) ?></td>
                                    <td><?= htmlspecialchars($row['diachi']) ?></td>
                                    <td class="text-center font-bold" style="color:#2563eb;"><?= number_format($row['tongtien'], 0, ',', '.') ?>&nbsp;đ</td>
                                    <td class="text-center"><?= date('d/m/Y H:i', $row['ngaytao']) ?></td>
                                    <td class="text-center">
                                        <span class="status-badge status-<?= $statusInfo['color'] ?>">
                                            <i class="fas <?= $statusInfo['icon'] ?>"></i>
                                            <?= $statusInfo['label'] ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <form method="POST" action="quanlidonhang.php<?= !empty($_GET['page']) ? '?page=' . $_GET['page'] : '' ?>" class="status-form">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="order_id" value="<?= $row['id'] ?>">
                                            <select name="new_status" class="status-select" onchange="this.form.submit()">
                                                <option value="PENDING"   <?= ($row['status'] ?? '') == 'PENDING'   ? 'selected' : '' ?>>Chờ xử lý</option>
                                                <option value="CONFIRMED" <?= ($row['status'] ?? '') == 'CONFIRMED' ? 'selected' : '' ?>>Đã xác nhận</option>
                                                <option value="SHIPPING"  <?= ($row['status'] ?? '') == 'SHIPPING'  ? 'selected' : '' ?>>Đang giao</option>
                                                <option value="DELIVERED" <?= ($row['status'] ?? '') == 'DELIVERED' ? 'selected' : '' ?>>Đã giao</option>
                                                <option value="CANCELLED" <?= ($row['status'] ?? '') == 'CANCELLED' ? 'selected' : '' ?>>Đã hủy</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn-detail" onclick="toggleDetail(<?= $row['id'] ?>, this)">Xem</button>
                                    </td>
                                    <td class="text-center doimau"> <a href="inhoadon.php?id=<?= $row['id'] ?>"
                                            target="_blank">In</a>
                                    </td>
                                </tr>
                                <tr class="detail-row" id="detail-row-<?= $row['id'] ?>">
                                    <td colspan="10">
                                        <div class="detail-content" id="detail-<?= $row['id'] ?>">
                                            <div id="detail-loading-<?= $row['id'] ?>">Đang tải...</div>
                                        </div>
                                    </td>
                                </tr>
                                <?php
                            } ?>
                        </tbody>
                    </table>

                    <div class="clear-both"></div>
                    <?php
                    include './pagination.php';
                    ?>
                    <div class="clear-both"></div>
                </div>
            </div>


        </div>
    </div>


    <script>
        /*Toggle dropdown list*/
        function toggleDD(myDropMenu) {
            document.getElementById(myDropMenu).classList.toggle("invisible");
        }

        /*Filter dropdown options*/
        function filterDD(myDropMenu, myDropMenuSearch) {
            var input, filter, ul, li, a, i;
            input = document.getElementById(myDropMenuSearch);
            filter = input.value.toUpperCase();
            div = document.getElementById(myDropMenu);
            a = div.getElementsByTagName("a");
            for (i = 0; i < a.length; i++) {
                if (a[i].innerHTML.toUpperCase().indexOf(filter) > -1) {
                    a[i].style.display = "";
                } else {
                    a[i].style.display = "none";
                }
            }
        }

        // Close the dropdown menu if the user clicks outside of it
        window.onclick = function (event) {
            if (!event.target.matches('.drop-button') && !event.target.matches('.drop-search')) {
                var dropdowns = document.getElementsByClassName("dropdownlist");
                for (var i = 0; i < dropdowns.length; i++) {
                    var openDropdown = dropdowns[i];
                    if (!openDropdown.classList.contains('invisible')) {
                        openDropdown.classList.add('invisible');
                    }
                }
            }
        }
    // Toggle order detail
    function toggleDetail(orderId, btn) {
        var detailRow = document.getElementById('detail-row-' + orderId);
        var isOpen = detailRow.classList.contains('open');
        
        // Close all other open details
        document.querySelectorAll('.detail-row.open').forEach(function(el) {
            el.classList.remove('open');
        });
        document.querySelectorAll('.btn-detail.active').forEach(function(el) {
            el.classList.remove('active');
            el.textContent = 'Xem';
        });
        
        if (!isOpen) {
            detailRow.classList.add('open');
            btn.classList.add('active');
            btn.textContent = 'Ẩn';
            
            // Load content if not loaded yet
            var loading = document.getElementById('detail-loading-' + orderId);
            if (loading) {
                fetch('ajax_order_detail.php?id=' + orderId)
                    .then(function(r) { return r.text(); })
                    .then(function(html) {
                        document.getElementById('detail-' + orderId).innerHTML = html;
                    })
                    .catch(function() {
                        document.getElementById('detail-' + orderId).innerHTML = '<p style="color:red;">Lỗi tải dữ liệu</p>';
                    });
            }
        }
    }
    </script>

<?php mysqli_close($con); ?>
</body>

</html>