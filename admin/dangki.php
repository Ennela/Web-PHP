<!DOCTYPE html>
<html>

<head>
    <title>Trang đăng kí </title>
    <link href="https://unpkg.com/tailwindcss@^1.0/dist/tailwind.min.css"
          rel="stylesheet">
    <script src="https://kit.fontawesome.com/a81368914c.js"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="UTF-8">
</head>
<style>
    .i {
        color: #d9d9d9;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .i i {
        transition: .3s;
    }

    .input-div > div {
        position: relative;
        height: 45px;
    }

    .input-div > div > h5 {
        position: absolute;
        left: 10px;
        top: 50%;
        transform: translateY(-50%);
        color: #999;
        font-size: 18px;
        transition: .3s;
    }

    .input-div:before,
    .input-div:after {
        content: '';
        position: absolute;
        bottom: -2px;
        width: 0%;
        height: 2px;
        background-color: #38d39f;
        transition: .4s;
    }

    .input-div:before {
        right: 50%;
    }

    .input-div:after {
        left: 50%;
    }

    .input-div.focus:before,
    .input-div.focus:after {
        width: 50%;
    }

    .input-div.focus > div > h5 {
        top: -5px;
        font-size: 15px;
    }

    .input-div.focus > .i > i {
        color: #38d39f;
    }
</style>
<body class="bg-gray-300" style="font-family: Roboto;">
<?php
    include './connect_db.php';
    include './function.php';
    $kiemtra = 'khong co';
    if (isset($_GET['action']) && $_GET['action'] == 'reg') {
        if (isset($_POST['taikhoan']) && !empty($_POST['taikhoan'])
            && isset($_POST['matkhau'])
            && !empty($_POST['matkhau'])
        ) {
            $taikhoan = $_POST['taikhoan'];
            $matkhau = $_POST['matkhau'];
            $ngaysinh = $_POST['ngaysinh'];
            $hoten = $_POST['hoten'];

            $check = validateDateTime($ngaysinh);
        }
    if ($check) {
        $ngaysinh = strtotime($ngaysinh);
        $result = mysqli_query($con,
            "INSERT INTO `tbl_qlthanhvien` (hoten,taikhoan,matkhau,ngaysinh,ngaytao,ngaycapnhat) VALUES ('$hoten', '$taikhoan','$matkhau', ' $ngaysinh', '"
            . time() . "', '" . time() . "')");
    if (!$result) {

         if (strpos(mysqli_error($con), "Duplicate entry") !== false) {
              ?>
              <script type="text/javascript">
                    alert('Tài khoản đã tồn tại');
                 window.location.href = 'dangki.php';
        </script>
    <?php
        }
        }
        mysqli_close($con);
        }
        else
        {
        $kiemtra = 'co ne ';
    ?>
        <script type="text/javascript">
            alert('Vui lòng nhập đúng định dạng ngày sinh');
            window.location.href = 'dangki.php';
        </script>
    <?php
        }
        if ($kiemtra != 'co ne')
        {
    ?>
        <script type="text/javascript">
            alert('Đăng kí thành công, mời bạn đăng nhập ');
            window.location.href = 'dangnhap.php';
        </script>
    <?php
        }
        } else
        {
    ?>
        <div class="h-screen flex justify-center items-center">
            <div class="bg-white rounded-lg w-2/5 px-16 py-16">
                <form action="dangki.php?action=reg" method="Post">
                    <div class="flex font-bold justify-center">
                        <img class="h-20 w-20"
                             src="https://raw.githubusercontent.com/sefyudem/Responsive-Login-Form/master/img/avatar.svg">
                    </div>
                    <h2 class="text-3xl text-center text-gray-700 mb-4">Đăng
                        kí</h2>
                    <div class="input-div border-b-2 relative grid my-5 py-1 focus:outline-none"
                         style="grid-template-columns: 7% 93%;">
                        <div class="i">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="div">
                            <h5>Tài khoản</h5>
                            <input name="taikhoan" type="text"
                                   class="absolute w-full h-full py-2 px-3 outline-none inset-0 text-gray-700"
                                   style="background:none;">
                        </div>
                    </div>
                    <div class="input-div border-b-2 relative grid my-5 py-1 focus:outline-none"
                         style="grid-template-columns: 7% 93%;">
                        <div class="i">
                            <i class="fas fa-lock"></i>
                        </div>
                        <div class="div">
                            <h5>Mật khẩu</h5>
                            <input name="matkhau" type="password"
                                   class="absolute w-full h-full py-2 px-3 outline-none inset-0 text-gray-700"
                                   style="background:none;">
                        </div>
                    </div>
                    <div class="input-div border-b-2 relative grid my-5 py-1 focus:outline-none"
                         style="grid-template-columns: 7% 93%;">
                        <div class="i">
                            <i class="far fa-address-card"></i>
                        </div>
                        <div class="div">
                            <h5>Họ tên</h5>
                            <input name="hoten" type="text"
                                   class="absolute w-full h-full py-2 px-3 outline-none inset-0 text-gray-700"
                                   style="background:none;">
                        </div>
                    </div>
                    <div class="input-div border-b-2 relative grid my-5 py-1 focus:outline-none"
                         style="grid-template-columns: 7% 93%;">
                        <div class="i">
                            <i class="fas fa-birthday-cake"></i>
                        </div>
                        <div class="div">
                            <h5>Ngày sinh(DD-MM-YYYY)</h5>
                            <input name="ngaysinh" type="text"
                                   class="absolute w-full h-full py-2 px-3 outline-none inset-0 text-gray-700"
                                   style="background:none;">
                        </div>
                    </div>

                    <button
                            type="submit"
                            class="w-full mt-2 py-2 rounded-full bg-green-600 text-gray-100  focus:outline-none">
                        Xác nhận
                    </button>

                </form>

            </div>
        </div>
        <?php
    } ?>
</body>
<script>
    const inputs = document.querySelectorAll("input");


    function addcl() {
        let parent = this.parentNode.parentNode;
        parent.classList.add("focus");
    }

    function remcl() {
        let parent = this.parentNode.parentNode;
        if (this.value == "") {
            parent.classList.remove("focus");
        }
    }


    inputs.forEach(input => {
        input.addEventListener("focus", addcl);
        input.addEventListener("blur", remcl);
    });
</script>
</html>