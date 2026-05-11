<?php

    session_start();
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

?>
<?php
    include_once 'connect_db.php';
    include_once 'function.php';
    if (isset($_GET['id'])) {
        $sql_query = "SELECT * FROM tbl_qlbaidang WHERE id=" . $_GET['id'];
        $result_set = mysqli_query($con, $sql_query);
        $fetched_row = mysqli_fetch_array($result_set);
    }
    if (isset($_POST['btn-save'])) {
        // Bi?n d? li?u d?u vào
        $nguoiphutrach = $_POST['nguoiphutrach'];
        $tieude = $_POST['tieude'];
        $chedo = $_POST['chedo'];
        $noidung = $_POST['noidung'];

        if (isset($_FILES['anhdaidien']) && !empty($_FILES['anhdaidien']['name'][0])) {
            $uploadedFiles = $_FILES['anhdaidien'];
            $result1 = uploadFiles($uploadedFiles);
            if (!empty($result1['errors'])) {
                $error = $result1['errors'];
            } else {
                $anhdaidien = $result1['path'];
            }
            $sql_query1
                = "UPDATE tbl_qlbaidang SET anhdaidien='$anhdaidien' WHERE id=" . $_GET['id'];
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
                = "UPDATE tbl_qlbaidang SET anhgiuoithieu1='$anhgiuoithieu1' WHERE id=" . $_GET['id'];
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
                = "UPDATE tbl_qlbaidang SET anhgiuoithieu2='$anhgiuoithieu2' WHERE id=" . $_GET['id'];
            $result_set3 = mysqli_query($con, $sql_query3);

        }
        $sql_query
            = "UPDATE tbl_qlbaidang SET nguoiphutrach='$nguoiphutrach',tieude='$tieude',chedo='$chedo',noidung='$noidung',ngaytao='"
            . time() . "',ngaycapnhat='" . time() . "' WHERE id=" . $_GET['id'];
        // Truy v?n SQL d? chèn d? li?u vào co s? d? li?u'

        // sql query execution function

        if (mysqli_query($con, $sql_query)) {

            ?>
            <script type="text/javascript">
                alert('D? li?u dã du?c dua vào');
                window.location.href = 'quanlibaidang.php';
            </script>
            <?php
        } else {
            ?>
            <script type="text/javascript">
                alert('D? li?u dua vào không h?p l? vui lòng ki?m tra l?i');
                window.location.href = 'quanlibaidang.php';
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
    <title>Trang ch?</title>
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
                           placeholder="Tìm ki?m"
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
                                        class="fa fa-user fa-fw"></i> Ðang nh?p
                            </a>
                            <div class="border border-gray-800"></div>
                            <a class="block p-2 text-sm text-white no-underline hover:bg-blue-800 hover:no-underline"
                               href="?login=dangxuat"
                               style="width: 120px;"><i
                                        class="fas fa-sign-out-alt fa-fw"></i>
                                Ðang xu?t</a>
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
                    <a class="block py-1 pl-1 text-white no-underline align-middle border-b-2 hover:border-pink-500 border-gray-800 md:py-3 hover:text-white"
                       href="trangchu.php">
                        <i class="pr-0 fas fa-tasks md:pr-3"></i><span
                                class="block pb-1 text-xs text-gray-600 md:pb-0 md:text-base md:text-gray-400 md:inline-block">Trang ch?</span>
                    </a>
                </li>

                <li class="flex-1 mr-3">
                    <a class="block py-1 pl-1 text-white no-underline align-middle border-b-2 border-gray-800 md:py-3 hover:text-white hover:border-blue-700"
                       href="./quanlisanpham.php">

                        <i class="fas fa-dolly md:pr-3"></i>
                        <span
                                class="block pb-1 text-xs text-gray-600 md:pb-0 md:text-base md:text-gray-400 md:inline-block">Qu?n lí s?n ph?m</span>
                    </a>

                </li>
                <li class="flex-1 mr-3">
                    <a class="block py-1 pl-1 text-white no-underline align-middle border-b-2 border-gray-800 md:py-3 hover:text-white hover:border-yellow-400"
                       href="./quanlikhachhang.php">

                        <i class="far fa-address-card md:pr-3"></i>
                        <span
                                class="block pb-1 text-xs text-gray-600 md:pb-0 md:text-base md:text-gray-400 md:inline-block">Qu?n lí Khách hàng</span>
                    </a>
                </li>
                <li class="flex-1 mr-3">
                    <a class="block py-1 pl-1 text-white no-underline align-middle border-b-2 border-gray-800 md:py-3 hover:text-white border-blue-500"
                       href="./quanlibaidang.php">

                        <i class="fas fa-align-left md:pr-3"></i>
                        <span
                                class="block pb-1 text-xs text-gray-600 md:pb-0 md:text-base md:text-gray-400 md:inline-block">Qu?n lí bài dang</span>
                    </a>
                </li>

                <li class="flex-1 mr-3">
                    <a class="block py-1 pl-0 text-white no-underline align-middle border-b-2 border-gray-800 md:py-3 md:pl-1 hover:text-white hover:border-pink-400"
                       href="./quanlithanhvien.php">
                        <i class="far fa-address-book md:pr-3"></i>

                        <span
                                class="block pb-1 text-xs text-gray-600 md:pb-0 md:text-base md:text-gray-400 md:inline-block">Qu?n lí thành viên</span>
                    </a>
                </li>
                <li class="mr-3 flex-1">
                    <a class="block py-1 md:py-3 pl-0 md:pl-1 align-middle text-white no-underline hover:text-white border-b-2 border-gray-800 hover:border-green-400"
                       href="./quanlidonhang.php">
                        <i class="fas fa-wallet md:pr-3"></i>


                        <span
                                class="pb-1 md:pb-0 text-xs md:text-base text-gray-600 md:text-gray-400 block md:inline-block">Qu?n lí don hàng</span>
                    </a>
                </li>
            </ul>
        </div>


    </div>

    <div class="main-content flex-1 bg-gray-100 mt-12 md:mt-2 pb-24 md:pb-5">

        <div class="bg-gray-800 pt-3">
            <div class="rounded-tl-3xl bg-gradient-to-r from-blue-900 to-gray-800 p-4 shadow text-2xl text-white">
                <h3 class="font-bold pl-2">S?a bài vi?t</h3>
            </div>
        </div>


        <div class="flex flex-wrap p-6">
            <div class="w-1/2 m-auto ">
                <form class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4"
                      enctype="multipart/form-data" method="post">
                    <label class="block">
                        <span class="text-gray-700">Tên tiêu d?:</span>
                        <input class="form-input border-2  border-blue-400 rounded-lg mt-1 block w-full"
                               placeholder="" value="<?php echo $fetched_row['tieude']; ?>"
                               type="text" name="tieude">
                    </label>
                    <label class="block">
                        <span class="text-gray-700">Ngu?i ph? trách:</span>
                        <input class="form-input border-2  border-blue-400 rounded-lg mt-1 block w-full"
                               placeholder="" value="<?php echo $fetched_row['nguoiphutrach']; ?>"
                               type="text" name="nguoiphutrach">
                    </label>
                    <label class="block">
                        <span class="text-gray-700">Ch? d?:</span>
                        <select name="chedo"
                                class="form-select border-2 rounded-lg border-blue-400 block w-full mt-1">
                            <option>?n</option>
                            <option>Hi?n</option>
                        </select>
                    </label>
                    <!--                    ?nh-->


                    <div class="mt-5">
                        <label for="avatar">Ch?n ?nh d?i di?n: </label>
                        <?php if (!empty($fetched_row['anhdaidien'])) { ?>
                            <img width="200px" height="200px" src="<?= $fetched_row['anhdaidien'] ?>"/><br/>
                        <?php } ?>
                        <input type="file"
                               id="avatar" name="anhdaidien"
                               accept="image/png, image/jpeg">
                    </div>
                    <div class="mt-5">
                        <label for="avatar">Ch?n ?nh gi?i thi?u 1: </label>
                        <?php if (!empty($fetched_row['anhgiuoithieu1'])) { ?>
                            <img width="200px" height="200px" src="<?= $fetched_row['anhgiuoithieu1'] ?>"/><br/>
                        <?php } ?>
                        <input type="file"
                               id="avatar" name="anhgiuoithieu1"
                               accept="image/png, image/jpeg">
                    </div>
                    <div class="mt-5">
                        <label for="avatar">Ch?n ?nh gi?i thi?u 2: </label>
                        <?php if (!empty($fetched_row['anhgiuoithieu2'])) { ?>
                            <img width="200px" height="200px" src="<?= $fetched_row['anhgiuoithieu2'] ?>"/><br/>
                        <?php } ?>
                        <input type="file"
                               id="avatar" name="anhgiuoithieu2"
                               accept="image/png, image/jpeg">
                    </div>
                    <!--                    Textarea-->
                    <label class="block mt-2">
                        <span class="text-gray-700">N?i dung:</span>
                        <textarea name="noidung"
                                  class="form-textarea border-2 rounded-lg border-blue-400  mt-1 block w-full"
                                  placeholder="" rows="3"
                        ><?php echo $fetched_row['noidung']; ?>
                        </textarea>
                    </label>
                    <div class="mt-3 text-right">
                        <button class="px-4 py-2 text-white bg-red-500 rounded shadow-xl">
                            <a href="quanlibaidang.php">Quay l?i</a>
                        </button>
                        <button type="submit" name="btn-save"
                                class="ml-3 mt-3 h-10 w-32 bg-blue-600 rounded text-white hover:bg-blue-700">
                            T?o
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
