<?php

    session_start();
    include './connect_db.php';
?>
<?php
    // session_destroy();
    // unset('dangnhap');
    if (isset($_POST['dangnhap'])) {
        $taikhoan = $_POST['taikhoan'];
        $matkhau = $_POST['matkhau'];
        if ($taikhoan == '' || $matkhau == '') {
            ?>
            <script type="text/javascript">
                alert('Vui lòng nhập đủ thông tin');
                window.location.href = 'dangnhap.php';
            </script>
            <?php
        } else {
            // Prepared statement chống SQL injection
            $stmt = mysqli_prepare($con, "SELECT * FROM tbl_qlthanhvien WHERE taikhoan = ? LIMIT 1");
            mysqli_stmt_bind_param($stmt, "s", $taikhoan);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $row_dangnhap = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);

            if ($row_dangnhap && password_verify($matkhau, $row_dangnhap['matkhau'])) {
                // Đăng nhập thành công với bcrypt hash
                $_SESSION['dangnhap1'] = $row_dangnhap['hoten'];
                $_SESSION['manv'] = $row_dangnhap['id'];
                header('Location: trangchu.php');
                exit;
            } elseif ($row_dangnhap && $row_dangnhap['matkhau'] === $matkhau) {
                // Fallback: mật khẩu cũ chưa hash — đăng nhập OK + tự động hash lại
                $hashedPassword = password_hash($matkhau, PASSWORD_BCRYPT);
                $updateStmt = mysqli_prepare($con, "UPDATE tbl_qlthanhvien SET matkhau = ? WHERE id = ?");
                mysqli_stmt_bind_param($updateStmt, "si", $hashedPassword, $row_dangnhap['id']);
                mysqli_stmt_execute($updateStmt);
                mysqli_stmt_close($updateStmt);

                $_SESSION['dangnhap1'] = $row_dangnhap['hoten'];
                $_SESSION['manv'] = $row_dangnhap['id'];
                header('Location: trangchu.php');
                exit;
            } else {
                ?>
                <script type="text/javascript">
                    alert('Sai tài khoản hoặc mật khẩu');
                    window.location.href = 'dangnhap.php';
                </script>
                <?php
            }
        }
    }
?>
<!DOCTYPE html>
<html>

<head>
    <title>Trang đăng nhập</title>
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
<div class="h-screen flex justify-center items-center">
    <div class="bg-white rounded-lg w-2/5 px-16 py-16">
        <form method="post">
            <div class="flex font-bold justify-center">
                <img class="h-20 w-20"
                     src="https://raw.githubusercontent.com/sefyudem/Responsive-Login-Form/master/img/avatar.svg">
            </div>
            <h2 class="text-3xl text-center text-gray-700 mb-4">Đăng nhập</h2>
            <div class="input-div border-b-2 relative grid my-5 py-1 focus:outline-none"
                 style="grid-template-columns: 7% 93%;">
                <div class="i">
                    <i class="fas fa-user"></i>
                </div>
                <div class="div">
                    <h5>Tài khoản</h5>
                    <input type="text" name="taikhoan"
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

            <button type="submit" name="dangnhap"
                    class="w-full py-2 rounded-full bg-green-600 text-gray-100  focus:outline-none">
                Xác nhận
            </button>
        </form>
    </div>
</div>
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
</body>

</html>