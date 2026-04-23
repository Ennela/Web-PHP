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
    include './connect_db.php';
    $lay = mysqli_query($con, "SELECT SUM(tongtien) as 'tong'  FROM oder WHERE MONTH(FROM_UNIXTIME(ngaytao)) = MONTH(CURRENT_DATE()) AND YEAR(FROM_UNIXTIME(ngaytao)) = YEAR(CURRENT_DATE()) AND status = 'DELIVERED'");
    $tongdoanhthu = mysqli_fetch_array($lay);
    $laysp = mysqli_query($con, "SELECT * FROM tbl_qlsanpham ");
    $tongsanpham = mysqli_num_rows($laysp);
    $laybd = mysqli_query($con, "SELECT * FROM tbl_qlbaidang ");
    $tongbaidang = mysqli_num_rows($laybd);
    $layadmin = mysqli_query($con, "SELECT * FROM tbl_qlthanhvien ");
    $tongthanhvien = mysqli_num_rows($layadmin);
    $laykh = mysqli_query($con, "SELECT * FROM tbl_tkkhachhang ");
    $tongsokh = mysqli_num_rows($laykh);

    $sanphamthang1 = mysqli_query($con, "SELECT * FROM oder WHERE MONTH(FROM_UNIXTIME(ngaytao)) = 1 AND YEAR(FROM_UNIXTIME(ngaytao)) = YEAR(CURRENT_DATE()) AND status = 'DELIVERED'");
    $count1 = mysqli_num_rows($sanphamthang1);
    $sanphamthang2 = mysqli_query($con, "SELECT * FROM oder WHERE MONTH(FROM_UNIXTIME(ngaytao)) = 2 AND YEAR(FROM_UNIXTIME(ngaytao)) = YEAR(CURRENT_DATE()) AND status = 'DELIVERED'");
    $count2 = mysqli_num_rows($sanphamthang2);
    $sanphamthang3 = mysqli_query($con, "SELECT * FROM oder WHERE MONTH(FROM_UNIXTIME(ngaytao)) = 3 AND YEAR(FROM_UNIXTIME(ngaytao)) = YEAR(CURRENT_DATE()) AND status = 'DELIVERED'");
    $count3 = mysqli_num_rows($sanphamthang3);
    $sanphamthang4 = mysqli_query($con, "SELECT * FROM oder WHERE MONTH(FROM_UNIXTIME(ngaytao)) = 4 AND YEAR(FROM_UNIXTIME(ngaytao)) = YEAR(CURRENT_DATE()) AND status = 'DELIVERED'");
    $count4 = mysqli_num_rows($sanphamthang4);
    $sanphamthang5 = mysqli_query($con, "SELECT * FROM oder WHERE MONTH(FROM_UNIXTIME(ngaytao)) = 5 AND YEAR(FROM_UNIXTIME(ngaytao)) = YEAR(CURRENT_DATE()) AND status = 'DELIVERED'");
    $count5 = mysqli_num_rows($sanphamthang5);
    $sanphamthang6 = mysqli_query($con, "SELECT * FROM oder WHERE MONTH(FROM_UNIXTIME(ngaytao)) = 6 AND YEAR(FROM_UNIXTIME(ngaytao)) = YEAR(CURRENT_DATE()) AND status = 'DELIVERED'");
    $count6 = mysqli_num_rows($sanphamthang6);
    $sanphamthang7 = mysqli_query($con, "SELECT * FROM oder WHERE MONTH(FROM_UNIXTIME(ngaytao)) = 7 AND YEAR(FROM_UNIXTIME(ngaytao)) = YEAR(CURRENT_DATE()) AND status = 'DELIVERED'");
    $count7 = mysqli_num_rows($sanphamthang7);
    $sanphamthang8 = mysqli_query($con, "SELECT * FROM oder WHERE MONTH(FROM_UNIXTIME(ngaytao)) = 8 AND YEAR(FROM_UNIXTIME(ngaytao)) = YEAR(CURRENT_DATE()) AND status = 'DELIVERED'");
    $count8 = mysqli_num_rows($sanphamthang8);
    $sanphamthang9 = mysqli_query($con, "SELECT * FROM oder WHERE MONTH(FROM_UNIXTIME(ngaytao)) = 9 AND YEAR(FROM_UNIXTIME(ngaytao)) = YEAR(CURRENT_DATE()) AND status = 'DELIVERED'");
    $count9 = mysqli_num_rows($sanphamthang9);
    $sanphamthang10 = mysqli_query($con, "SELECT * FROM oder WHERE MONTH(FROM_UNIXTIME(ngaytao)) = 10 AND YEAR(FROM_UNIXTIME(ngaytao)) = YEAR(CURRENT_DATE()) AND status = 'DELIVERED'");
    $count10 = mysqli_num_rows($sanphamthang10);
    $sanphamthang11 = mysqli_query($con, "SELECT * FROM oder WHERE MONTH(FROM_UNIXTIME(ngaytao)) = 11 AND YEAR(FROM_UNIXTIME(ngaytao)) = YEAR(CURRENT_DATE()) AND status = 'DELIVERED'");
    $count11 = mysqli_num_rows($sanphamthang11);
    $sanphamthang12 = mysqli_query($con, "SELECT * FROM oder WHERE MONTH(FROM_UNIXTIME(ngaytao)) = 12 AND YEAR(FROM_UNIXTIME(ngaytao)) = YEAR(CURRENT_DATE()) AND status = 'DELIVERED'");
    $count12 = mysqli_num_rows($sanphamthang12);


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="ie=edge" http-equiv="X-UA-Compatible">
    <title>Trang chủ</title>
    <meta content="name" name="author">
    <meta content="description here" name="description">
    <meta content="keywords,here" name="keywords">
    <link href="https://use.fontawesome.com/releases/v5.3.1/css/all.css" rel="stylesheet">
    <link href="https://unpkg.com/tailwindcss@^2/dist/tailwind.min.css" rel="stylesheet">
    <!--Replace with your tailwind.css once created-->
    <link href="https://afeld.github.io/emoji-css/emoji.css" rel="stylesheet"> <!--Totally optional :) -->
    <script crossorigin="anonymous"
            integrity="sha256-xKeoJ50pzbUGkpQxDYHD7o7hxe0LaOGeguUidbq6vis="
            src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.bundle.min.js"></script>
    <link href="css/test.css" rel="stylesheet">
</head>


<body class="mt-12 font-sans tracking-normal leading-normal bg-gray-800">

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
                    <div class="absolute search-icon" style="top: 1rem; left: .8rem;">
                        <svg class="w-4 h-4 text-white pointer-events-none fill-current"
                             viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12.9 14.32a8 8 0 1 1 1.41-1.41l5.35 5.33-1.42 1.42-5.33-5.34zM8 14A6 6 0 1 0 8 2a6 6 0 0 0 0 12z"></path>
                        </svg>
                    </div>
                </span>
        </div>

        <div class="flex justify-between content-center pt-2 w-full md:w-1/3 md:justify-end">
            <ul class="flex flex-1 justify-between items-center list-reset md:flex-none">

                <li class="flex-1 md:flex-none md:mr-3">
                    <div class="inline-block relative">
                        <button class="text-white drop-button focus:outline-none" onclick="toggleDD('myDropdown')"><span
                                    class="pr-2"><i
                                        class="em em-robot_face"></i></span> <?php echo 'Xin chào, ' . $_SESSION['dangnhap1'] ?>
                            <svg class="inline h-3 fill-current" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/>
                            </svg>
                        </button>
                        <div class="overflow-auto absolute right-0 invisible z-30 p-3 mt-3 text-white bg-gray-800 dropdownlist"
                             id="myDropdown">

                            <a class="block p-2 text-sm text-white no-underline hover:bg-blue-800 hover:no-underline"
                               href="dangnhap.php"
                               style="width: 120px;"><i
                                        class="fa fa-user fa-fw"></i> Đăng nhập </a>
                            <div class="border border-gray-800"></div>
                            <a class="block p-2 text-sm text-white no-underline hover:bg-blue-800 hover:no-underline"
                               href="?login=dangxuat"
                               style="width: 120px;"><i
                                        class="fas fa-sign-out-alt fa-fw"></i> Đăng xuất</a>
                        </div>
                    </div>
                </li>
            </ul>
        </div>
    </div>

</nav>


<div class="flex flex-col md:flex-row">

    <div class="fixed bottom-0 z-10 mt-12 w-full h-16 bg-gray-800 shadow-xl md:relative md:h-screen md:w-48">
        <!--menu-->
        <div class="justify-between content-center text-left md:mt-12 md:w-50 md:fixed md:left-0 md:top-0 md:content-start">
            <ul class="flex flex-row px-1 py-0 text-center list-reset md:flex-col md:py-3 md:px-2 md:text-left">
                <li class="flex-1 mr-3">
                    <a class="block py-1 pl-1 text-white no-underline align-middle border-b-2 border-pink-500 border-gray-800 md:py-3 hover:text-white"
                       href="./trangchu.php">
                        <i class="pr-0 fas fa-tasks md:pr-3"></i><span
                                class="block pb-1 text-xs text-gray-600 md:pb-0 md:text-base md:text-gray-400 md:inline-block">Trang chủ</span>
                    </a>
                </li>

                <li class="flex-1 mr-3">
                    <a class="block py-1 pl-1 text-white no-underline align-middle border-b-2 border-gray-800 md:py-3 hover:text-white hover:border-blue-700"
                       href="./quanlisanpham.php">

                        <i class="fas fa-dolly md:pr-3"></i>
                        <span
                                class="block pb-1 text-xs text-gray-600 md:pb-0 md:text-base md:text-gray-400 md:inline-block">Quản lí sản phẩm</span>
                    </a>

                </li>
                <li class="flex-1 mr-3">
                    <a class="block py-1 pl-1 text-white no-underline align-middle border-b-2 border-gray-800 md:py-3 hover:text-white hover:border-yellow-400"
                       href="./quanlikhachhang.php">

                        <i class="far fa-address-card md:pr-3"></i>
                        <span
                                class="block pb-1 text-xs text-gray-600 md:pb-0 md:text-base md:text-gray-400 md:inline-block">Quản lí Khách hàng</span>
                    </a>
                </li>
                <li class="flex-1 mr-3">
                    <a class="block py-1 pl-1 text-white no-underline align-middle border-b-2 border-gray-800 md:py-3 hover:text-white hover:border-blue-500"
                       href="./quanlibaidang.php">

                        <i class="fas fa-align-left md:pr-3"></i>
                        <span
                                class="block pb-1 text-xs text-gray-600 md:pb-0 md:text-base md:text-gray-400 md:inline-block">Quản lí bài đăng</span>
                    </a>
                </li>

                <li class="flex-1 mr-3">
                    <a class="block py-1 pl-0 text-white no-underline align-middle border-b-2 border-gray-800 md:py-3 md:pl-1 hover:text-white hover:border-pink-400"
                       href="./quanlithanhvien.php">
                        <i class="far fa-address-book md:pr-3"></i>

                        <span
                                class="block pb-1 text-xs text-gray-600 md:pb-0 md:text-base md:text-gray-400 md:inline-block">Quản lí thành viên</span>
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

    <div class="flex-1 pb-24 mt-12 bg-gray-100 main-content md:mt-2 md:pb-5">

        <div class="pt-3 bg-gray-800">
            <div class="p-4 text-2xl text-white bg-gradient-to-r from-blue-900 to-gray-800 rounded-tl-3xl shadow">
                <h3 class="pl-2 font-bold">Trang chủ</h3>
            </div>
        </div>


        <div class="flex flex-wrap">
            <div class="p-6 w-full md:w-1/2 xl:w-1/3">
                <!--Metric Card-->
                <div class="p-5 bg-gradient-to-b from-green-200 to-green-100 rounded-lg border-b-4 border-green-600 shadow-xl">
                    <div class="flex flex-row items-center">
                        <div class="flex-shrink pr-4">
                            <div class="p-5 bg-green-600 rounded-full"><i class="fa fa-wallet fa-2x fa-inverse"></i>
                            </div>
                        </div>
                        <div class="flex-1 text-right md:text-center">
                            <h5 class="font-bold text-gray-600 uppercase">Tổng thu nhập tháng</h5>
                            <h3 class="text-3xl font-bold"><?= number_format($tongdoanhthu['tong'], 0, ",", ".") ?> VND <span class="text-green-500"><i
                                            class="fas fa-caret-up"></i></span></h3>
                        </div>
                    </div>
                </div>
                <!--/Metric Card-->
            </div>
            <div class="p-6 w-full md:w-1/2 xl:w-1/3">
                <!--Metric Card-->
                <div class="p-5 bg-gradient-to-b from-pink-200 to-pink-100 rounded-lg border-b-4 border-pink-500 shadow-xl">
                    <div class="flex flex-row items-center">
                        <div class="flex-shrink pr-4">
                            <div class="p-5 bg-pink-600 rounded-full"><i class="fas fa-users fa-2x fa-inverse"></i>
                            </div>
                        </div>
                        <div class="flex-1 text-right md:text-center">
                            <h5 class="font-bold text-gray-600 uppercase">Tài khoản admin</h5>
                            <h3 class="text-3xl font-bold"><?= $tongthanhvien ?> <span class="text-pink-500"><i
                                            class="fas fa-exchange-alt"></i></span></h3>
                        </div>
                    </div>
                </div>
                <!--/Metric Card-->
            </div>
            <div class="p-6 w-full md:w-1/2 xl:w-1/3">
                <!--Metric Card-->
                <div class="p-5 bg-gradient-to-b from-yellow-200 to-yellow-100 rounded-lg border-b-4 border-yellow-600 shadow-xl">
                    <div class="flex flex-row items-center">
                        <div class="flex-shrink pr-4">
                            <div class="p-5 bg-yellow-600 rounded-full"><i
                                        class="fas fa-user-plus fa-2x fa-inverse"></i></div>
                        </div>
                        <div class="flex-1 text-right md:text-center">
                            <h5 class="font-bold text-gray-600 uppercase">Tài khoản khách hàng</h5>
                            <h3 class="text-3xl font-bold"><?= $tongsokh ?> <span class="text-yellow-600"><i
                                            class="fas fa-caret-up"></i></span></h3>
                        </div>
                    </div>
                </div>
                <!--/Metric Card-->
            </div>
            <div class="p-6 w-full md:w-1/2 xl:w-1/3">
                <!--Metric Card-->
                <div class="p-5 bg-gradient-to-b from-blue-200 to-blue-100 rounded-lg border-b-4 border-blue-500 shadow-xl">
                    <div class="flex flex-row items-center">
                        <div class="flex-shrink pr-4">
                            <div class="p-5 bg-blue-600 rounded-full"><i class="fas fa-server fa-2x fa-inverse"></i>
                            </div>
                        </div>
                        <div class="flex-1 text-right md:text-center">
                            <h5 class="font-bold text-gray-600 uppercase">Số bài đăng</h5>
                            <h3 class="text-3xl font-bold"><?= $tongbaidang ?> bài</h3>
                        </div>
                    </div>
                </div>
                <!--/Metric Card-->
            </div>
            <div class="p-6 w-full md:w-1/2 xl:w-1/3">
                <!--Metric Card-->
                <div class="p-5 bg-gradient-to-b from-indigo-200 to-indigo-100 rounded-lg border-b-4 border-indigo-500 shadow-xl">
                    <div class="flex flex-row items-center">
                        <div class="flex-shrink pr-4">
                            <div class="p-5 bg-indigo-600 rounded-full"><i class="fas fa-tasks fa-2x fa-inverse"></i>
                            </div>
                        </div>
                        <div class="flex-1 text-right md:text-center">
                            <h5 class="font-bold text-gray-600 uppercase">Tổng sản phẩm</h5>
                            <h3 class="text-3xl font-bold"><?= $tongsanpham ?> sản phẩm</h3>
                        </div>
                    </div>
                </div>
                <!--/Metric Card-->
            </div>
            <div class="p-6 w-full md:w-1/2 xl:w-1/3">
                <!--Metric Card-->
                <div class="p-5 bg-gradient-to-b from-red-200 to-red-100 rounded-lg border-b-4 border-red-500 shadow-xl">
                    <div class="flex flex-row items-center">
                        <div class="flex-shrink pr-4">
                            <div class="p-5 bg-red-600 rounded-full"><i class="fas fa-inbox fa-2x fa-inverse"></i></div>
                        </div>
                        <div class="flex-1 text-right md:text-center">
                            <h5 class="font-bold text-gray-600 uppercase">Báo lỗi</h5>
                            <h3 class="text-3xl font-bold">0 <span class="text-red-500"><i class="fas fa-caret-up"></i></span>
                            </h3>
                        </div>
                    </div>
                </div>
                <!--/Metric Card-->
            </div>
        </div>
        <div class="p-6 w-full">
            <!--Graph Card-->
            <div class="bg-white rounded-lg border-transparent shadow-xl">
                <div class="p-2 text-gray-800 uppercase bg-gradient-to-b from-gray-300 to-gray-100 rounded-tl-lg rounded-tr-lg border-b-2 border-gray-300">
                    <h5 class="font-bold text-gray-600 uppercase">Doanh thu</h5>
                </div>
                <div class="p-5">
                    <canvas class="chartjs" height="undefined" id="chartjs-1" width="undefined"></canvas>
                    <script>
                        new Chart(document.getElementById("chartjs-1"), {
                            "type": "bar",
                            "data": {
                                "labels": ["Tháng 1", "Tháng 2", "Tháng 3", "Tháng 4", "Tháng 5", "Tháng 6", "Tháng 7", "Tháng 8", "Tháng 9", "Tháng 10", "Tháng 11", "Tháng 12"],
                                "datasets": [{
                                    "label": "Đơn hàng",
                                    "data": [
                                        <?= $count1 ?>, <?= $count2 ?>, <?= $count3 ?>, <?= $count4 ?>, <?= $count5 ?>, <?= $count6 ?>, <?= $count7 ?>, <?= $count8 ?>, <?= $count9 ?>, <?= $count10 ?>, <?= $count11 ?>, <?= $count12 ?>
                                    ],
                                    "fill": false,
                                    "backgroundColor": ["rgba(75, 19, 19, 0.2)", "rgba(75, 19, 19, 0.2)", "rgba(75, 19, 19, 0.2)", "rgba(75, 19, 19, 0.2)", "rgba(75, 19, 19, 0.2)", "rgba(75, 19, 19, 0.2)", "rgba(75, 19, 19, 0.2)", "rgba(75, 19, 19, 0.2)", "rgba(75, 19, 19, 0.2)", "rgba(75, 19, 19, 0.2)", "rgba(75, 19, 19, 0.2)", "rgba(75, 19, 19, 0.2)"],

                                    "borderWidth": 1
                                }]
                            },
                            "options": {
                                "scales": {
                                    "yAxes": [{
                                        "ticks": {
                                            "beginAtZero": true
                                        }
                                    }]
                                }
                            }
                        });
                    </script>
                </div>
            </div>
            <!--/Graph Card-->
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
