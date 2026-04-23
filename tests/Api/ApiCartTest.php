<?php
/**
 * API TEST: Cart Update AJAX
 * Test endpoint shop/cart_update_ajax.php
 */
use PHPUnit\Framework\TestCase;

class ApiCartTest extends TestCase
{
    private string $apiUrl;
    private string $cookie = '';

    protected function setUp(): void
    {
        $this->apiUrl = BASE_URL . 'shop/cart_update_ajax.php';
        $resp = TestHelper::httpRequest(BASE_URL . 'auth/dangnhap.php', 'POST', [
            'taikhoan' => 'test05', 'matkhau' => 'kudo-kun', 'dangnhap' => '1',
        ]);
        $this->cookie = $resp['cookies'];
    }

    /** TC-A-27: POST thiếu masp → error */
    public function testCartUpdateMissingMasp(): void
    {
        $resp = TestHelper::httpRequest($this->apiUrl, 'POST_JSON', [
            'quantity' => 2,
        ], $this->cookie ?: 'dummy');
        $json = json_decode($resp['body'], true);
        $this->assertNotNull($json);
        $this->assertFalse($json['success']);
    }

    /** TC-A-28: POST delete SP → success */
    public function testCartDeleteProduct(): void
    {
        $resp = TestHelper::httpRequest($this->apiUrl, 'POST_JSON', [
            'masp' => 999, 'action' => 'delete',
        ], $this->cookie ?: 'dummy');
        $json = json_decode($resp['body'], true);
        $this->assertNotNull($json);
        // Delete luôn success (xóa key không tồn tại cũng ok)
        $this->assertTrue($json['success']);
    }

    /** TC-A-29: Response format đúng khi delete */
    public function testCartResponseFormat(): void
    {
        $resp = TestHelper::httpRequest($this->apiUrl, 'POST_JSON', [
            'masp' => 1, 'action' => 'delete',
        ], $this->cookie ?: 'dummy');
        $json = json_decode($resp['body'], true);
        if ($json && $json['success']) {
            $this->assertArrayHasKey('grandTotal', $json);
            $this->assertArrayHasKey('cartCount', $json);
        }
    }
}
