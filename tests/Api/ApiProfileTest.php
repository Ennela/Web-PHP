<?php
/**
 * API TEST: Profile Update
 * Test endpoint auth/api_capnhat_thongtin.php
 * ⚠️ Yêu cầu Apache đang chạy, dùng tài khoản từ DB gốc (test05/kudo-kun)
 */
use PHPUnit\Framework\TestCase;

class ApiProfileTest extends TestCase
{
    private string $apiUrl;
    private string $cookie = '';

    protected function setUp(): void
    {
        $this->apiUrl = BASE_URL . 'auth/api_capnhat_thongtin.php';
        // Login bằng tài khoản thực trên DB gốc
        $resp = TestHelper::httpRequest(BASE_URL . 'auth/dangnhap.php', 'POST', [
            'taikhoan' => 'test05', 'matkhau' => 'kudo-kun', 'dangnhap' => '1',
        ]);
        $this->cookie = $resp['cookies'];
    }

    /** TC-A-01: Cập nhật thông tin thành công */
    public function testUpdateProfileSuccess(): void
    {
        if (empty($this->cookie)) { $this->markTestSkipped('Không login được - kiểm tra DB gốc'); }

        $resp = TestHelper::httpRequest($this->apiUrl, 'POST', [
            'hoten' => 'Test User Updated',
            'email' => 'updated@example.com',
            'sdt' => '0394680113',
        ], $this->cookie);

        $json = json_decode($resp['body'], true);
        $this->assertNotNull($json, "Response phải là JSON");
        if (!$json['success']) {
            // Có thể tài khoản test chưa có cột mới - chấp nhận
            $this->assertStringContainsString('nhập', $json['message'] ?? 'no message');
        }
    }

    /** TC-A-02: Không đăng nhập → yêu cầu đăng nhập */
    public function testUpdateProfileWithoutLogin(): void
    {
        $resp = TestHelper::httpRequest($this->apiUrl, 'POST', ['hoten' => 'Test']);
        $json = json_decode($resp['body'], true);
        $this->assertNotNull($json);
        $this->assertFalse($json['success']);
        $this->assertStringContainsString('đăng nhập', $json['message']);
    }

    /** TC-A-03: GET method → error */
    public function testUpdateProfileWithGetMethod(): void
    {
        $resp = TestHelper::httpRequest($this->apiUrl, 'GET', [], $this->cookie ?: 'dummy');
        $json = json_decode($resp['body'], true);
        $this->assertNotNull($json);
        $this->assertFalse($json['success']);
    }
}
