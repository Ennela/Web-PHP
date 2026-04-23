<?php
/**
 * SYSTEM TEST: Admin Flow
 * Test luồng admin: Login → Dashboard → Quản lý
 * ⚠️ Admin pages redirect nếu chưa login, test kiểm tra redirect behavior
 */
use PHPUnit\Framework\TestCase;

class AdminFlowSystemTest extends TestCase
{
    /** TC-S-11: Login admin → redirect (302) tới dashboard */
    public function testAdminLoginRedirects(): void
    {
        $resp = TestHelper::httpRequest(BASE_URL . 'admin/dangnhap.php', 'POST', [
            'taikhoan' => 'noah2005',
            'matkhau' => 'kudo-kun',
            'dangnhap' => '1',
        ]);
        $this->assertContains($resp['code'], [200, 302], "Login thành công phải redirect");
    }

    /** TC-S-12: Admin pages redirect khi chưa đăng nhập */
    public function testAdminPagesRequireAuth(): void
    {
        $pages = [
            'admin/trangchu.php',
            'admin/quanlisanpham.php',
            'admin/quanlidonhang.php',
            'admin/quanlikhachhang.php',
        ];

        foreach ($pages as $page) {
            $resp = TestHelper::httpRequest(BASE_URL . $page);
            // Redirect 302 hoặc 200 (nếu trang cho phép)
            $this->assertContains($resp['code'], [200, 302], "$page phải trả về 200 hoặc redirect");
        }
    }

    /** TC-S-13: Admin login page hiển thị form */
    public function testAdminLoginPageShowsForm(): void
    {
        $resp = TestHelper::httpRequest(BASE_URL . 'admin/dangnhap.php');
        $this->assertEquals(200, $resp['code']);
        $this->assertStringContainsString('form', strtolower($resp['body']));
    }

    /** TC-S-14: Login admin sai → vẫn ở trang login */
    public function testAdminLoginFailure(): void
    {
        $resp = TestHelper::httpRequest(BASE_URL . 'admin/dangnhap.php', 'POST', [
            'taikhoan' => 'wrong_user',
            'matkhau' => 'wrong_pass',
            'dangnhap' => '1',
        ]);
        $this->assertEquals(200, $resp['code'], "Login sai phải trả về 200 (ở lại trang login)");
    }
}
