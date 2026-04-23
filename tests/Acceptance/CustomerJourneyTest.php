<?php
/**
 * ACCEPTANCE TEST: Customer Journey
 * Kịch bản: Khách hàng duyệt shop → xem SP → thêm giỏ → đặt hàng → tra cứu
 */
use PHPUnit\Framework\TestCase;

class CustomerJourneyTest extends TestCase
{
    private string $cookie = '';

    protected function setUp(): void
    {
        $this->cookie = TestHelper::loginCustomer();
    }

    /** TC-AC-01: Khách vào trang chủ thành công */
    public function testStep1_VisitHomepage(): void
    {
        $resp = TestHelper::httpRequest(BASE_URL . 'home/trangchu.php');
        $this->assertEquals(200, $resp['code']);
        $this->assertNotEmpty($resp['body']);
    }

    /** TC-AC-02: Duyệt shop, xem danh sách sản phẩm */
    public function testStep2_BrowseShop(): void
    {
        $resp = TestHelper::httpRequest(BASE_URL . 'shop/shop.php');
        $this->assertEquals(200, $resp['code']);
    }

    /** TC-AC-03: Xem chi tiết sản phẩm */
    public function testStep3_ViewProductDetail(): void
    {
        $resp = TestHelper::httpRequest(BASE_URL . 'shop/chitietsanpham.php?masp=1');
        $this->assertEquals(200, $resp['code']);
    }

    /** TC-AC-04: Thêm sản phẩm vào giỏ hàng */
    public function testStep4_AddToCart(): void
    {
        $resp = TestHelper::httpRequest(
            BASE_URL . 'shop/giohang.php?masp=1&quantity=1&size=42',
            'GET', [], $this->cookie
        );
        $this->assertContains($resp['code'], [200, 302]);
    }

    /** TC-AC-05: Xem giỏ hàng */
    public function testStep5_ViewCart(): void
    {
        $resp = TestHelper::httpRequest(BASE_URL . 'shop/giohang.php', 'GET', [], $this->cookie);
        $this->assertEquals(200, $resp['code']);
    }

    /** TC-AC-06: Tra cứu đơn hàng (khách đã đăng nhập) */
    public function testStep6_TrackOrders(): void
    {
        $resp = TestHelper::httpRequest(BASE_URL . 'shop/tradonhang.php', 'GET', [], $this->cookie);
        $this->assertEquals(200, $resp['code']);
    }

    /** TC-AC-07: Xem chi tiết đơn hàng */
    public function testStep7_ViewOrderDetail(): void
    {
        $resp = TestHelper::httpRequest(
            BASE_URL . 'shop/chitietdonhang.php?id=1',
            'GET', [], $this->cookie
        );
        $this->assertContains($resp['code'], [200, 302]);
    }
}
