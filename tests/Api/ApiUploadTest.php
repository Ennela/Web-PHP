<?php
/**
 * API TEST: Upload Avatar
 * Test endpoint auth/api_upload_avatar.php
 */
use PHPUnit\Framework\TestCase;

class ApiUploadTest extends TestCase
{
    private string $apiUrl;
    private string $cookie = '';

    protected function setUp(): void
    {
        $this->apiUrl = BASE_URL . 'auth/api_upload_avatar.php';
        $this->cookie = TestHelper::loginCustomer();
    }

    /** TC-A-31: Không có file → error */
    public function testUploadWithoutFile(): void
    {
        $resp = TestHelper::httpRequest($this->apiUrl, 'POST', [], $this->cookie);
        $json = json_decode($resp['body'], true);
        $this->assertFalse($json['success']);
    }

    /** TC-A-32: Không đăng nhập → error */
    public function testUploadWithoutLogin(): void
    {
        $resp = TestHelper::httpRequest($this->apiUrl, 'POST', []);
        $json = json_decode($resp['body'], true);
        $this->assertFalse($json['success']);
        $this->assertStringContainsString('đăng nhập', $json['message']);
    }

    /** TC-A-33: GET method → error */
    public function testUploadWithGetMethod(): void
    {
        $resp = TestHelper::httpRequest($this->apiUrl, 'GET', [], $this->cookie);
        $json = json_decode($resp['body'], true);
        $this->assertFalse($json['success']);
    }
}
