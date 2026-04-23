<?php
/**
 * INTEGRATION TEST: Authentication
 * Test đăng nhập/đăng ký khách hàng và admin với DB thực
 */

use PHPUnit\Framework\TestCase;

class AuthIntegrationTest extends TestCase
{
    private mysqli $con;

    protected function setUp(): void
    {
        $this->con = TestHelper::getConnection();
    }

    protected function tearDown(): void
    {
        // Xoá user test tạm 
        $this->con->query("DELETE FROM `tbl_tkkhachhang` WHERE `username` LIKE 'integration_test_%'");
    }

    /**
     * TC-I-01: Đăng ký tài khoản mới → dữ liệu lưu đúng trong DB
     */
    public function testRegisterNewCustomer(): void
    {
        $username = 'integration_test_' . uniqid();
        $password = 'pass123456';
        $hoten = 'Integration Test User';

        $stmt = $this->con->prepare(
            "INSERT INTO `tbl_tkkhachhang` (`username`, `password`, `hoten`, `ngaytao`) VALUES (?, ?, ?, ?)"
        );
        $now = time();
        $stmt->bind_param('sssi', $username, $password, $hoten, $now);
        $result = $stmt->execute();
        $newId = $stmt->insert_id;
        $stmt->close();

        $this->assertTrue($result, "Insert phải thành công");
        $this->assertGreaterThan(0, $newId, "ID mới phải > 0");

        // Verify data trong DB
        $check = $this->con->query("SELECT * FROM `tbl_tkkhachhang` WHERE `makh` = $newId");
        $row = $check->fetch_assoc();

        $this->assertEquals($username, $row['username'], "Username phải khớp");
        $this->assertEquals($password, $row['password'], "Password phải khớp");
        $this->assertEquals($hoten, $row['hoten'], "Họ tên phải khớp");
    }

    /**
     * TC-I-02: Đăng nhập đúng username/password → tìm thấy user
     */
    public function testLoginWithCorrectCredentials(): void
    {
        $result = $this->con->query(
            "SELECT * FROM `tbl_tkkhachhang` WHERE `username` = 'testuser' AND `password` = 'test123456' LIMIT 1"
        );

        $this->assertEquals(1, $result->num_rows, "Phải tìm thấy đúng 1 user");
        $row = $result->fetch_assoc();
        $this->assertEquals('Nguyễn Test User', $row['hoten'], "Họ tên phải đúng");
    }

    /**
     * TC-I-03: Đăng nhập sai password → không tìm thấy
     */
    public function testLoginWithWrongPassword(): void
    {
        $result = $this->con->query(
            "SELECT * FROM `tbl_tkkhachhang` WHERE `username` = 'testuser' AND `password` = 'wrongpassword' LIMIT 1"
        );

        $this->assertEquals(0, $result->num_rows, "Không được tìm thấy user khi sai password");
    }

    /**
     * TC-I-04: Đăng nhập sai username → không tìm thấy
     */
    public function testLoginWithWrongUsername(): void
    {
        $result = $this->con->query(
            "SELECT * FROM `tbl_tkkhachhang` WHERE `username` = 'nonexistent_user' AND `password` = 'test123456' LIMIT 1"
        );

        $this->assertEquals(0, $result->num_rows, "Không được tìm thấy user khi sai username");
    }

    /**
     * TC-I-05: Đăng ký trùng username → thất bại (UNIQUE constraint)
     */
    public function testRegisterDuplicateUsername(): void
    {
        $this->expectException(\mysqli_sql_exception::class);
        $this->con->query(
            "INSERT INTO `tbl_tkkhachhang` (`username`, `password`, `hoten`) VALUES ('testuser', 'newpass', 'New User')"
        );
    }

    /**
     * TC-I-06: Đăng nhập admin đúng → tìm thấy
     */
    public function testAdminLoginCorrect(): void
    {
        $result = $this->con->query(
            "SELECT * FROM `tbl_qlthanhvien` WHERE `taikhoan` = 'admin_test' AND `matkhau` = 'admin123' LIMIT 1"
        );

        $this->assertEquals(1, $result->num_rows, "Admin phải đăng nhập được");
        $row = $result->fetch_assoc();
        $this->assertEquals('Admin Test', $row['hoten']);
    }

    /**
     * TC-I-07: Đăng nhập admin sai → không tìm thấy
     */
    public function testAdminLoginWrong(): void
    {
        $result = $this->con->query(
            "SELECT * FROM `tbl_qlthanhvien` WHERE `taikhoan` = 'admin_test' AND `matkhau` = 'wrong_pass' LIMIT 1"
        );

        $this->assertEquals(0, $result->num_rows, "Admin sai pass phải thất bại");
    }
}
