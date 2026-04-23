<?php

    session_start();
    if (!isset($_SESSION['dangnhap1'])) {
        header('Location: canhbao.php');
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

?>
<?php
    include_once 'connect_db.php';
    include_once 'function.php';
    require_once dirname(__DIR__) . '/includes/inventory_helper.php';

    if (isset($_GET['masp'])) {
        $sql_query = "SELECT * FROM tbl_qlsanpham WHERE masp=" . $_GET['masp'];
        $result_set = mysqli_query($con, $sql_query);
        $fetched_row = mysqli_fetch_array($result_set);
        // Load tồn kho hiện tại
        $currentStock = getStockByProduct($con, (int)$_GET['masp']);
    }
    if (isset($_POST['btn-save'])) {
        // Biến dữ liệu đầu vào

        $tensp = $_POST['tensp'];
        $giasanpham = $_POST['giasanpham'];
        $giagoc = $_POST['giagoc'];
        $noidung = $_POST['noidung'];

        $nhomsp = $_POST['nhomsp'];


        if (isset($_FILES['anhdaidien']) && !empty($_FILES['anhdaidien']['name'][0])) {
            $uploadedFiles = $_FILES['anhdaidien'];
            $result1 = uploadFiles($uploadedFiles);
            if (!empty($result1['errors'])) {
                $error = $result1['errors'];
            } else {
                $anhdaidien = $result1['path'];
            }
            $sql_query1
                = "UPDATE tbl_qlsanpham SET anhdaidien='$anhdaidien' WHERE masp=" . $_GET['masp'];

            $result_set1 = mysqli_query($con, $sql_query1);

        }
        if (isset($_FILES['anhgiuoithieu1']) && !empty($_FILES['anhgiuoithieu1']['name'][0])) {
            $uploadedFiles1 = $_FILES['anhgiuoithieu1'];
            $result2 = uploadFiles($uploadedFiles1);
            if (!empty($result2['errors'])) {
                $error = $result2['errors'];
            } else {
                $anhgiuoithieu1 = $result2['path'];
            }
            $sql_query2
                = "UPDATE tbl_qlsanpham SET anhgiuoithieu1='$anhgiuoithieu1' WHERE masp=" . $_GET['masp'];

            $result_set2 = mysqli_query($con, $sql_query2);
        }
        if (isset($_FILES['anhgiuoithieu2']) && !empty($_FILES['anhgiuoithieu2']['name'][0])) {
            $uploadedFiles2 = $_FILES['anhgiuoithieu2'];
            $result3 = uploadFiles($uploadedFiles2);
            if (!empty($result3['errors'])) {
                $error = $result3['errors'];
            } else {
                $anhgiuoithieu2 = $result3['path'];
            }
            $sql_query3
                = "UPDATE tbl_qlsanpham SET anhgiuoithieu2='$anhgiuoithieu2' WHERE masp=" . $_GET['masp'];
            $result_set3 = mysqli_query($con, $sql_query3);
        }
        $sql_query
            = "UPDATE tbl_qlsanpham SET tensp='$tensp',giasanpham='$giasanpham',giagoc='$giagoc',noidung='$noidung',ngaycapnhat='"
            . time() . "',nhomsp='$nhomsp' WHERE masp=" . $_GET['masp'];

        // Truy vấn SQL để chèn dữ liệu vào cơ sở dữ liệu'
        // sql query execution function

        if (mysqli_query($con, $sql_query) && !isset($error)) {

            // === CẬP NHẬT TỒN KHO ===
            if (isset($_POST['stock']) && is_array($_POST['stock'])) {
                $masp = (int)$_GET['masp'];
                $now = time();
                foreach ($_POST['stock'] as $size => $qty) {
                    $size = (int)$size;
                    $qty = max(0, (int)$qty);
                    $sql_stock = "INSERT INTO `tbl_tonkho` (`masp`, `size`, `soluong`, `ngaytao`, `ngaycapnhat`) 
                                  VALUES ($masp, $size, $qty, $now, $now) 
                                  ON DUPLICATE KEY UPDATE `soluong` = $qty, `ngaycapnhat` = $now";
                    mysqli_query($con, $sql_stock);
                }
            }

            ?>
            <script type="text/javascript">
                alert('Dữ liệu đã được thay đổi');
                window.location.href = 'quanlisanpham.php';
            </script>
            <?php
        } else {
            ?>
            <script type="text/javascript">
                alert('Dữ liệu thay vào không hợp lệ vui lòng kiểm tra lại');
                window.location.href = 'quanlisanpham.php';
            </script>
            <?php
        }
    }
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="ie=edge" http-equiv="X-UA-Compatible">
    <title>Quản lí sản phẩm</title>
    <meta content="name" name="author">
    <meta content="description here" name="description">
    <meta content="keywords,here" name="keywords">
    <link href="https://use.fontawesome.com/releases/v5.3.1/css/all.css"
          rel="stylesheet">
    <link href="https://unpkg.com/tailwindcss@^2/dist/tailwind.min.css"
          rel="stylesheet">
    <!--Replace with your tailwind.css once created-->
    <link href="https://afeld.github.io/emoji-css/emoji.css" rel="stylesheet">
    <!--Totally optional :) -->
    <script crossorigin="anonymous"
            integrity="sha256-xKeoJ50pzbUGkpQxDYHD7o7hxe0LaOGeguUidbq6vis="
            src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.bundle.min.js"></script>
    <link href="css/test.css" rel="stylesheet">
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
                    <input class="px-2 py-3 pl-10 w-full leading-normal text-white bg-gray-900 rounded border border-transparent transition appearance-none focus:outline-none focus:border-gray-400"
                           placeholder="Tìm kiếm"
                           type="search">
                    <div class="absolute search-icon"
                         style="top: 1rem; left: .8rem;">
                        <svg class="w-4 h-4 text-white pointer-events-none fill-current"
                             viewBox="0 0 20 20"
                             xmlns="http://www.w3.org/2000/svg">
                            <path d="M12.9 14.32a8 8 0 1 1 1.41-1.41l5.35 5.33-1.42 1.42-5.33-5.34zM8 14A6 6 0 1 0 8 2a6 6 0 0 0 0 12z"></path>
                        </svg>
                    </div>
                </span>
        </div>

        <div class="flex justify-between content-center pt-2 w-full md:w-1/3 md:justify-end">
            <ul class="flex flex-1 justify-between items-center list-reset md:flex-none">

                <li class="flex-1 md:flex-none md:mr-3">
                    <div class="inline-block relative">
                        <button class="text-white drop-button focus:outline-none"
                                onclick="toggleDD('myDropdown')"><span
                                    class="pr-2"><i
                                        class="em em-robot_face"></i></span>
                            Xin chào, <?php
                                echo $_SESSION['dangnhap1'] ?>
                            <svg class="inline h-3 fill-current"
                                 viewBox="0 0 20 20"
                                 xmlns="http://www.w3.org/2000/svg">
                                <path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/>
                            </svg>
                        </button>
                        <div class="overflow-auto absolute right-0 invisible z-30 p-3 mt-3 text-white bg-gray-800 dropdownlist"
                             id="myDropdown">

                            <a class="block p-2 text-sm text-white no-underline hover:bg-blue-800 hover:no-underline"
                               href="dangnhap.php"
                               style="width: 120px;"><i
                                        class="fa fa-user fa-fw"></i> Đăng nhập
                            </a>
                            <div class="border border-gray-800"></div>
                            <a class="block p-2 text-sm text-white no-underline hover:bg-blue-800 hover:no-underline"
                               href="?login=dangxuat"
                               style="width: 120px;"><i
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

        <div class="md:mt-12 md:w-50 md:fixed md:left-0 md:top-0 content-center md:content-start text-left justify-between">
            <ul class="list-reset flex flex-row md:flex-col py-0 md:py-3 px-1 md:px-2 text-center md:text-left">
                <li class="mr-3 flex-1">
                    <a class="block py-1 md:py-3 pl-1 align-middle text-white no-underline hover:text-white border-b-2 border-gray-800 hover:border-pink-500"
                       href="./trangchu.php">
                        <i class="fas fa-tasks pr-0 md:pr-3"></i><span
                                class="pb-1 md:pb-0 text-xs md:text-base text-gray-600 md:text-gray-400 block md:inline-block">Trang chủ</span>
                    </a>
                </li>

                <li class="mr-3 flex-1">
                    <a class="block py-1 md:py-3 pl-1 align-middle text-white no-underline hover:text-white border-b-2 border-gray-800 border-blue-700"
                       href="./quanlisanpham.php">

                        <i class="fas fa-dolly md:pr-3"></i>
                        <span
                                class="pb-1 md:pb-0 text-xs md:text-base text-gray-600 md:text-gray-400 block md:inline-block">Quản lí sản phẩm</span>
                    </a>
                </li>

                <li class="mr-3 flex-1">
                    <a class="block py-1 md:py-3 pl-1 align-middle text-white no-underline hover:text-white border-b-2 border-gray-800 hover:border-yellow-400"
                       href="./quanlikhachhang.php">

                        <i class="far fa-address-card md:pr-3"></i>
                        <span
                                class="pb-1 md:pb-0 text-xs md:text-base text-gray-600 md:text-gray-400 block md:inline-block">Quản lí Khách hàng</span>
                    </a>
                </li>
                <li class="mr-3 flex-1">
                    <a class="block py-1 md:py-3 pl-1 align-middle text-white no-underline hover:text-white border-b-2 border-gray-800 hover:border-blue-500"
                       href="./quanlibaidang.php">

                        <i class="fas fa-align-left md:pr-3"></i>
                        <span
                                class="pb-1 md:pb-0 text-xs md:text-base text-gray-600 md:text-gray-400 block md:inline-block">Quản lí bài đăng</span>
                    </a>
                </li>

                <li class="mr-3 flex-1">
                    <a class="block py-1 md:py-3 pl-0 md:pl-1 align-middle text-white no-underline hover:text-white border-b-2 border-gray-800 hover:border-pink-400"
                       href="./quanlithanhvien.php">
                        <i class="far fa-address-book md:pr-3"></i>

                        <span
                                class="pb-1 md:pb-0 text-xs md:text-base text-gray-600 md:text-gray-400 block md:inline-block">Quản lí thành viên</span>
                    </a>
                </li>
                <li class="mr-3 flex-1">
                    <a class="block py-1 md:py-3 pl-0 md:pl-1 align-middle text-white no-underline hover:text-white border-b-2 border-gray-800 hover:border-green-400"
                       href="./quanlidonhang.php">
                        <i class="fas fa-wallet md:pr-3"></i>
                        <span
                                class="pb-1 md:pb-0 text-xs md:text-base text-gray-600 md:text-gray-400 block md:inline-block">Quản lí đơn hàng</span>
                    </a>
                </li>
            </ul>
        </div>


    </div>

    <div class="main-content flex-1 bg-gray-100 mt-12 md:mt-2 pb-24 md:pb-5">

        <div class="bg-gray-800 pt-3">
            <div class="rounded-tl-3xl bg-gradient-to-r from-blue-900 to-gray-800 p-4 shadow text-2xl text-white">
                <h3 class="font-bold pl-2">Chỉnh sửa sản phẩm</h3>
            </div>
        </div>


        <div class="flex flex-wrap p-6">
            <div class="w-1/2 m-auto ">
                <form enctype="multipart/form-data" method="post"
                      class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
                    <label class="block">
                        <span class="text-gray-700">Tên sản phẩm:</span>
                        <input type="text" name="tensp" value="<?php echo $fetched_row['tensp']; ?>"
                               class="form-input border-2  border-blue-400 rounded-lg mt-1 block w-full"
                               placeholder="">
                    </label>
                    <label class="block">
                        <span class="text-gray-700">Giá sản phẩm(đơn vị VND):</span>
                        <input type="price" name="giasanpham" value="<?php echo $fetched_row['giasanpham']; ?>"
                               class="form-input border-2  border-blue-400 rounded-lg mt-1 block w-full"
                               placeholder="">
                    </label>
                    <label class="block">
                        <span class="text-gray-700">Giá gốc(đơn vị VND):</span>
                        <input type="price" name="giagoc" value="<?php echo $fetched_row['giagoc']; ?>"
                               class="form-input border-2  border-blue-400 rounded-lg mt-1 block w-full"
                               placeholder="">
                    </label>
                    <label class="block">
                        <span class="text-gray-700">Nhóm sản phẩm</span>
                        <select name="nhomsp"
                                class="form-select  border-2 rounded-lg border-blue-400 block w-full mt-1">
                            <option>Giày nam</option>
                            <option>Giày nữ</option>
                        </select>
                    </label>
                    <!--                    ảnh-->
                    <div class="mt-5">
                        <label for="avatar">Chọn ảnh đại diện: </label>
                        <?php if (!empty($fetched_row['anhdaidien'])) { ?>
                            <img width="200px" height="200px" src="<?= $fetched_row['anhdaidien'] ?>"/><br/>
                        <?php } ?>
                        <input type="file"
                               id="avatar" name="anhdaidien"
                               accept="image/png, image/jpeg">
                    </div>
                    <div class="mt-5">
                        <label for="avatar">Chọn ảnh giới thiệu 1: </label>
                        <?php if (!empty($fetched_row['anhgiuoithieu1'])) { ?>
                            <img width="200px" height="200px" src="<?= $fetched_row['anhgiuoithieu1'] ?>"/><br/>
                        <?php } ?>
                        <input type="file"
                               id="avatar" name="anhgiuoithieu1" value=""
                               accept="image/png, image/jpeg">
                    </div>
                    <div class="mt-5">
                        <label for="avatar">Chọn ảnh giới thiệu 2: </label>
                        <?php if (!empty($fetched_row['anhgiuoithieu2'])) { ?>
                            <img width="200px" height="200px" src="<?= $fetched_row['anhgiuoithieu2'] ?>"/><br/>
                        <?php } ?>
                        <input type="file"
                               id="avatar" name="anhgiuoithieu2"
                               accept="image/png, image/jpeg">
                    </div>


                    <!--                    Textarea-->
                    <label class="block mt-2">
                        <span class="text-gray-700">Nội dung:</span>
                        <textarea
                                name="noidung"
                                class="form-textarea border-2 rounded-lg border-blue-400  mt-1 block w-full"
                                placeholder="" rows="3"><?= $fetched_row['noidung'] ?>
                        </textarea>
                    </label>

                    <!-- ===== QUẢN LÝ TỒN KHO ===== -->
                    <div class="mt-6 p-4 bg-gray-50 rounded-lg border-2 border-gray-200">
                        <h4 class="text-lg font-bold text-gray-700 mb-3">
                            <i class="fas fa-boxes" style="color:#3b82f6; margin-right:6px;"></i> Tồn kho theo Size
                        </h4>
                        <div class="grid grid-cols-3 gap-3">
                            <?php
                            $sizes = [36, 37, 38, 39, 40, 41, 42, 43, 44];
                            foreach ($sizes as $s):
                                $stockQty = $currentStock[$s] ?? 0;
                                $stockColor = $stockQty <= 0 ? 'border-red-400 bg-red-50' : ($stockQty <= 5 ? 'border-yellow-400 bg-yellow-50' : 'border-green-400 bg-green-50');
                            ?>
                            <div class="flex items-center gap-2 p-2 rounded-lg border-2 <?= $stockColor ?>">
                                <span class="font-bold text-gray-600 w-12">Size <?= $s ?></span>
                                <input type="number" name="stock[<?= $s ?>]" value="<?= $stockQty ?>" min="0" 
                                       class="form-input border border-gray-300 rounded px-2 py-1 w-20 text-center font-bold"
                                       style="-moz-appearance: textfield;">
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">
                            <i class="fas fa-info-circle mr-1"></i> Đặt số lượng = 0 để đánh dấu hết hàng
                        </p>
                    </div>

                    <div class="mt-3 text-right">
                        <button class="px-4 py-2 text-white bg-red-500 rounded shadow-xl">
                            <a href="quanlisanpham.php">Quay lại</a>
                        </button>
                        <button type="submit" name="btn-save"
                                class="ml-3 mt-3 h-10 w-32 bg-blue-600 rounded text-white hover:bg-blue-700">
                            Cập nhật
                        </button>
                    </div>
                </form>
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
</script>


</body>

</html>
