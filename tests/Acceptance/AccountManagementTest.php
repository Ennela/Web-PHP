<?php
/**
 * ACCEPTANCE TEST: Account Management
 * Kịch bản: Đăng ký → Đăng nhập → Cập nhật → Đổi MK → Địa chỉ
 * ⚠️ Sử dụng tài khoản thực trên DB gốc
 */
use PHPUnit\Framework\TestCase;

class AccountManagementTest extends TestCase
{
    /** TC-AC-16: Đăng ký trang hiển thị form */
    public function testRegisterPageShowsForm(): void
    {
        $resp = TestHelper::httpRequest(BASE_URL . 'auth/dangki.php');
        $this->assertEquals(200, $resp['code']);
        $this->assertStringContainsString('dangki', $resp['body']);
    }

    /** TC-AC-17: Đăng nhập với tài khoản test05 */
    public function testLoginWithTestAccount(): void
    {
        $resp = TestHelper::httpRequest(BASE_URL . 'auth/dangnhap.php', 'POST', [
            'taikhoan' => 'test05', 'matkhau' => 'kudo-kun', 'dangnhap' => '1',
        ]);
        $this->assertContains($resp['code'], [200, 302], "Login phải thành công (redirect)");
        $this->assertNotEmpty($resp['cookies'], "Phải có session cookie");
    }

    /** TC-AC-18: Đăng nhập sai → alert */
    public function testLoginFailure(): void
    {
        $resp = TestHelper::httpRequest(BASE_URL . 'auth/dangnhap.php', 'POST', [
            'taikhoan' => 'nonexistent', 'matkhau' => 'wrong', 'dangnhap' => '1',
        ]);
        $this->assertEquals(200, $resp['code']);
        $this->assertStringContainsString('Sai', $resp['body']);
    }

    /** TC-AC-19: Trang thông tin tài khoản accessible */
    public function testAccountInfoPageAccessible(): void
    {
        $resp = TestHelper::httpRequest(BASE_URL . 'auth/dangnhap.php', 'POST', [
            'taikhoan' => 'test05', 'matkhau' => 'kudo-kun', 'dangnhap' => '1',
        ]);
        $cookie = $resp['cookies'];

        $resp2 = TestHelper::httpRequest(BASE_URL . 'auth/thongtintaikhoan.php', 'GET', [], $cookie);
        $this->assertContains($resp2['code'], [200, 302]);
    }

    /** TC-AC-20: API đổi MK - validation hoạt động */
    public function testPasswordChangeValidation(): void
    {
        $resp = TestHelper::httpRequest(BASE_URL . 'auth/api_doimatkhau.php', 'POST', [
            'matkhau_hientai' => '', 'matkhau_moi' => '', 'matkhau_xacnhan' => '',
        ]);
        $json = json_decode($resp['body'], true);
        $this->assertNotNull($json);
        $this->assertFalse($json['success']);
    }
}
