<?php
/**
 * API TEST: Shoe Size Preferences
 * Test endpoint auth/api_sizegiay.php
 */
use PHPUnit\Framework\TestCase;

class ApiSizeTest extends TestCase
{
    private string $apiUrl;
    private string $cookie = '';

    protected function setUp(): void
    {
        $this->apiUrl = BASE_URL . 'auth/api_sizegiay.php';
        $resp = TestHelper::httpRequest(BASE_URL . 'auth/dangnhap.php', 'POST', [
            'taikhoan' => 'test05', 'matkhau' => 'kudo-kun', 'dangnhap' => '1',
        ]);
        $this->cookie = $resp['cookies'];
    }

    /** TC-A-20: Không đăng nhập → error */
    public function testAccessWithoutLogin(): void
    {
        $resp = TestHelper::httpRequest($this->apiUrl, 'GET');
        $json = json_decode($resp['body'], true);
        $this->assertFalse($json['success']);
        $this->assertStringContainsString('đăng nhập', $json['message']);
    }

    /** TC-A-21: GET danh sách → JSON */
    public function testGetSizeList(): void
    {
        if (empty($this->cookie)) { $this->markTestSkipped('Không login được'); }
        $resp = TestHelper::httpRequest($this->apiUrl, 'GET', [], $this->cookie);
        $json = json_decode($resp['body'], true);
        $this->assertTrue($json['success']);
        $this->assertIsArray($json['data']);
    }

    /** TC-A-22: Hệ size không hợp lệ → error */
    public function testSaveInvalidSizeSystem(): void
    {
        if (empty($this->cookie)) { $this->markTestSkipped('Không login được'); }
        $resp = TestHelper::httpRequest($this->apiUrl, 'POST', [
            'action' => 'save', 'he_size' => 'UK', 'size_value' => '8',
        ], $this->cookie);
        $json = json_decode($resp['body'], true);
        $this->assertFalse($json['success']);
    }

    /** TC-A-23: Thiếu size_value → error */
    public function testSaveMissingSizeValue(): void
    {
        if (empty($this->cookie)) { $this->markTestSkipped('Không login được'); }
        $resp = TestHelper::httpRequest($this->apiUrl, 'POST', [
            'action' => 'save', 'he_size' => 'EU', 'size_value' => '',
        ], $this->cookie);
        $json = json_decode($resp['body'], true);
        $this->assertFalse($json['success']);
    }

    /** TC-A-24: Action không hợp lệ → error */
    public function testInvalidAction(): void
    {
        if (empty($this->cookie)) { $this->markTestSkipped('Không login được'); }
        $resp = TestHelper::httpRequest($this->apiUrl, 'POST', [
            'action' => 'invalid',
        ], $this->cookie);
        $json = json_decode($resp['body'], true);
        $this->assertFalse($json['success']);
    }
}
