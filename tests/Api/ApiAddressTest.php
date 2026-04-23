<?php
/**
 * API TEST: Address Management
 * Test endpoint auth/api_diachi.php (GET + POST CRUD)
 * ⚠️ Dùng tài khoản test05 từ DB gốc
 */
use PHPUnit\Framework\TestCase;

class ApiAddressTest extends TestCase
{
    private string $apiUrl;
    private string $cookie = '';

    protected function setUp(): void
    {
        $this->apiUrl = BASE_URL . 'auth/api_diachi.php';
        $resp = TestHelper::httpRequest(BASE_URL . 'auth/dangnhap.php', 'POST', [
            'taikhoan' => 'test05', 'matkhau' => 'kudo-kun', 'dangnhap' => '1',
        ]);
        $this->cookie = $resp['cookies'];
    }

    /** TC-A-13: Không đăng nhập → error */
    public function testAccessWithoutLogin(): void
    {
        $resp = TestHelper::httpRequest($this->apiUrl, 'GET');
        $json = json_decode($resp['body'], true);
        $this->assertNotNull($json);
        $this->assertFalse($json['success']);
        $this->assertStringContainsString('đăng nhập', $json['message']);
    }

    /** TC-A-14: GET danh sách → JSON array */
    public function testGetAddressList(): void
    {
        if (empty($this->cookie)) { $this->markTestSkipped('Không login được'); }
        $resp = TestHelper::httpRequest($this->apiUrl, 'GET', [], $this->cookie);
        $json = json_decode($resp['body'], true);
        $this->assertTrue($json['success']);
        $this->assertIsArray($json['data']);
    }

    /** TC-A-15: POST thiếu field bắt buộc → error */
    public function testAddAddressMissingRequired(): void
    {
        if (empty($this->cookie)) { $this->markTestSkipped('Không login được'); }
        $resp = TestHelper::httpRequest($this->apiUrl, 'POST', [
            'action' => 'add', 'hoten' => '', 'sdt' => '', 'diachi_cuthe' => '',
        ], $this->cookie);
        $json = json_decode($resp['body'], true);
        $this->assertFalse($json['success']);
    }

    /** TC-A-16: POST SĐT sai format → error */
    public function testAddAddressInvalidPhone(): void
    {
        if (empty($this->cookie)) { $this->markTestSkipped('Không login được'); }
        $resp = TestHelper::httpRequest($this->apiUrl, 'POST', [
            'action' => 'add', 'hoten' => 'Test', 'sdt' => '12345', 'diachi_cuthe' => 'Addr',
        ], $this->cookie);
        $json = json_decode($resp['body'], true);
        $this->assertFalse($json['success']);
    }

    /** TC-A-17: Action không hợp lệ → error */
    public function testInvalidAction(): void
    {
        if (empty($this->cookie)) { $this->markTestSkipped('Không login được'); }
        $resp = TestHelper::httpRequest($this->apiUrl, 'POST', [
            'action' => 'unknown_action',
        ], $this->cookie);
        $json = json_decode($resp['body'], true);
        $this->assertFalse($json['success']);
        $this->assertStringContainsString('không hợp lệ', $json['message']);
    }
}
