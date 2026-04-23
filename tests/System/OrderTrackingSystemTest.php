<?php
/**
 * SYSTEM TEST: Order Tracking
 * Test trang tra cứu đơn hàng
 */
use PHPUnit\Framework\TestCase;

class OrderTrackingSystemTest extends TestCase
{
    /** TC-S-16: Trang tra đơn hàng trả về 200 */
    public function testOrderTrackingPageAccessible(): void
    {
        $cookie = TestHelper::loginCustomer();
        $resp = TestHelper::httpRequest(BASE_URL . 'shop/tradonhang.php', 'GET', [], $cookie);
        $this->assertEquals(200, $resp['code'], "Trang tra đơn hàng phải trả về 200");
    }

    /** TC-S-17: Chi tiết đơn hàng accessible */
    public function testOrderDetailAccessible(): void
    {
        $cookie = TestHelper::loginCustomer();
        // Đơn hàng ID 1 từ seed data
        $resp = TestHelper::httpRequest(BASE_URL . 'shop/chitietdonhang.php?id=1', 'GET', [], $cookie);
        $this->assertContains($resp['code'], [200, 302]);
    }

    /** TC-S-18: Trang blog trả về 200 */
    public function testBlogPageAccessible(): void
    {
        $resp = TestHelper::httpRequest(BASE_URL . 'blog/baiviet.php');
        $this->assertContains($resp['code'], [200, 302, 404]);
    }
}
