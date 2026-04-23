<?php
/**
 * SYSTEM TEST: Homepage & Pages
 * Test các trang chính trả về HTTP 200 và nội dung đúng
 */
use PHPUnit\Framework\TestCase;

class HomepageSystemTest extends TestCase
{
    /** TC-S-01: Trang chủ trả về 200 OK */
    public function testHomepageReturns200(): void
    {
        $resp = TestHelper::httpRequest(BASE_URL . 'home/trangchu.php');
        $this->assertEquals(200, $resp['code'], "Trang chủ phải trả về 200");
        $this->assertNotEmpty($resp['body'], "Body không được rỗng");
    }

    /** TC-S-02: Trang shop hiển thị sản phẩm */
    public function testShopPageShowsProducts(): void
    {
        $resp = TestHelper::httpRequest(BASE_URL . 'shop/shop.php');
        $this->assertEquals(200, $resp['code'], "Trang shop phải trả về 200");
        $this->assertStringContainsString('shop', strtolower($resp['body']), "Phải chứa nội dung shop");
    }

    /** TC-S-03: Trang đăng nhập KH trả về form */
    public function testCustomerLoginPage(): void
    {
        $resp = TestHelper::httpRequest(BASE_URL . 'auth/dangnhap.php');
        $this->assertEquals(200, $resp['code']);
        $this->assertStringContainsString('dangnhap', $resp['body'], "Phải chứa form đăng nhập");
    }

    /** TC-S-04: Trang admin login trả về form */
    public function testAdminLoginPage(): void
    {
        $resp = TestHelper::httpRequest(BASE_URL . 'admin/dangnhap.php');
        $this->assertEquals(200, $resp['code']);
        $this->assertStringContainsString('dangnhap', strtolower($resp['body']));
    }

    /** TC-S-05: Trang đăng ký trả về 200 */
    public function testRegisterPage(): void
    {
        $resp = TestHelper::httpRequest(BASE_URL . 'auth/dangki.php');
        $this->assertEquals(200, $resp['code']);
    }

    /** TC-S-06: Check.php diagnostic trả về 200 */
    public function testDiagnosticPage(): void
    {
        $resp = TestHelper::httpRequest(BASE_URL . 'check.php');
        $this->assertEquals(200, $resp['code']);
        $this->assertStringContainsString('Kiểm tra', $resp['body']);
    }
}
