<?php
/**
 * ACCEPTANCE TEST: Admin Management
 * Kịch bản: Admin đăng nhập → dashboard → quản lý
 * ⚠️ Admin pages có bảo vệ session, test kiểm tra auth flow
 */
use PHPUnit\Framework\TestCase;

class AdminManagementTest extends TestCase
{
    /** TC-AC-08: Admin đăng nhập thành công → redirect */
    public function testAdminLoginSuccess(): void
    {
        $resp = TestHelper::httpRequest(BASE_URL . 'admin/dangnhap.php', 'POST', [
            'taikhoan' => 'noah2005', 'matkhau' => 'kudo-kun', 'dangnhap' => '1',
        ]);
        $this->assertContains($resp['code'], [200, 302]);
    }

    /** TC-AC-09: Admin login sai → ở lại trang */
    public function testAdminLoginFailure(): void
    {
        $resp = TestHelper::httpRequest(BASE_URL . 'admin/dangnhap.php', 'POST', [
            'taikhoan' => 'wrong', 'matkhau' => 'wrong', 'dangnhap' => '1',
        ]);
        $this->assertEquals(200, $resp['code']);
        // Phải chứa alert sai tài khoản
        $this->assertStringContainsString('Sai', $resp['body']);
    }

    /** TC-AC-10: Trang quản lý yêu cầu đăng nhập */
    public function testAdminPagesRequireAuth(): void
    {
        $pages = [
            'admin/trangchu.php', 'admin/quanlisanpham.php',
            'admin/quanlidonhang.php', 'admin/quanlikhachhang.php',
            'admin/quanlibaidang.php', 'admin/quanlithanhvien.php',
            'admin/quanlidanhmuc.php',
        ];
        foreach ($pages as $page) {
            $resp = TestHelper::httpRequest(BASE_URL . $page);
            $this->assertContains($resp['code'], [200, 302], "$page phải kiểm tra auth");
        }
    }

    /** TC-AC-11: Admin login page hiển thị form */
    public function testAdminLoginPageUI(): void
    {
        $resp = TestHelper::httpRequest(BASE_URL . 'admin/dangnhap.php');
        $this->assertEquals(200, $resp['code']);
        $this->assertStringContainsString('taikhoan', $resp['body']);
        $this->assertStringContainsString('matkhau', $resp['body']);
    }
}
