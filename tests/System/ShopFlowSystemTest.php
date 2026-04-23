<?php
/**
 * SYSTEM TEST: Shop Flow
 * Test luồng: Shop → Chi tiết SP → Giỏ hàng
 */
use PHPUnit\Framework\TestCase;

class ShopFlowSystemTest extends TestCase
{
    /** TC-S-07: Trang shop hiển thị danh sách sản phẩm */
    public function testShopListsProducts(): void
    {
        $resp = TestHelper::httpRequest(BASE_URL . 'shop/shop.php');
        $this->assertEquals(200, $resp['code']);
        // Trang shop phải chứa nội dung sản phẩm (card hoặc giá)
        $hasProducts = str_contains($resp['body'], 'product-card') 
            || str_contains($resp['body'], 'giasanpham')
            || str_contains($resp['body'], 'card-title');
        $this->assertTrue($hasProducts, 'Shop phải hiển thị sản phẩm');
    }

    /** TC-S-08: Chi tiết sản phẩm hiển thị đúng */
    public function testProductDetailPage(): void
    {
        // Dùng SP ID từ DB gốc
        $resp = TestHelper::httpRequest(BASE_URL . 'shop/chitietsanpham.php?masp=1');
        $this->assertEquals(200, $resp['code']);
    }

    /** TC-S-09: Trang giỏ hàng accessible */
    public function testCartPageAccessible(): void
    {
        $resp = TestHelper::httpRequest(BASE_URL . 'shop/giohang.php');
        $this->assertEquals(200, $resp['code']);
    }

    /** TC-S-10: Trang đặt hàng accessible */
    public function testOrderPageAccessible(): void
    {
        $resp = TestHelper::httpRequest(BASE_URL . 'shop/infodathang.php');
        $this->assertContains($resp['code'], [200, 302], "Trang đặt hàng phải accessible");
    }
}
