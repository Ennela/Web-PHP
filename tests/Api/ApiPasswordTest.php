<?php
/**
 * API TEST: Change Password
 * Test endpoint auth/api_doimatkhau.php
 * ⚠️ Dùng tài khoản test05 từ DB gốc
 */
use PHPUnit\Framework\TestCase;

class ApiPasswordTest extends TestCase
{
    private string $apiUrl;
    private string $cookie = '';

    protected function setUp(): void
    {
        $this->apiUrl = BASE_URL . 'auth/api_doimatkhau.php';
        $resp = TestHelper::httpRequest(BASE_URL . 'auth/dangnhap.php', 'POST', [
            'taikhoan' => 'test05', 'matkhau' => 'kudo-kun', 'dangnhap' => '1',
        ]);
        $this->cookie = $resp['cookies'];
    }

    /** TC-A-07: Không đăng nhập → yêu cầu đăng nhập */
    public function testChangePasswordWithoutLogin(): void
    {
        $resp = TestHelper::httpRequest($this->apiUrl, 'POST', [
            'matkhau_hientai' => 'test', 'matkhau_moi' => 'newpass', 'matkhau_xacnhan' => 'newpass',
        ]);
        $json = json_decode($resp['body'], true);
        $this->assertFalse($json['success']);
        $this->assertStringContainsString('đăng nhập', $json['message']);
    }

    /** TC-A-08: Mật khẩu mới < 6 ký tự → error */
    public function testChangePasswordTooShort(): void
    {
        if (empty($this->cookie)) { $this->markTestSkipped('Không login được'); }
        $resp = TestHelper::httpRequest($this->apiUrl, 'POST', [
            'matkhau_hientai' => 'kudo-kun', 'matkhau_moi' => '123', 'matkhau_xacnhan' => '123',
        ], $this->cookie);
        $json = json_decode($resp['body'], true);
        $this->assertFalse($json['success']);
        $this->assertStringContainsString('6', $json['message']);
    }

    /** TC-A-09: Xác nhận không khớp → error */
    public function testChangePasswordConfirmMismatch(): void
    {
        if (empty($this->cookie)) { $this->markTestSkipped('Không login được'); }
        $resp = TestHelper::httpRequest($this->apiUrl, 'POST', [
            'matkhau_hientai' => 'kudo-kun', 'matkhau_moi' => 'newpass123', 'matkhau_xacnhan' => 'different',
        ], $this->cookie);
        $json = json_decode($resp['body'], true);
        $this->assertFalse($json['success']);
        $this->assertStringContainsString('không khớp', $json['message']);
    }

    /** TC-A-10: Mật khẩu mới = cũ → error */
    public function testChangePasswordSameAsOld(): void
    {
        if (empty($this->cookie)) { $this->markTestSkipped('Không login được'); }
        $resp = TestHelper::httpRequest($this->apiUrl, 'POST', [
            'matkhau_hientai' => 'kudo-kun', 'matkhau_moi' => 'kudo-kun', 'matkhau_xacnhan' => 'kudo-kun',
        ], $this->cookie);
        $json = json_decode($resp['body'], true);
        $this->assertFalse($json['success']);
        $this->assertStringContainsString('khác', $json['message']);
    }
}
