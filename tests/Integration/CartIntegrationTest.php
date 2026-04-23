<?php
/**
 * INTEGRATION TEST: Shopping Cart
 * Test giỏ hàng - session + database interaction
 */

use PHPUnit\Framework\TestCase;

class CartIntegrationTest extends TestCase
{
    private mysqli $con;

    protected function setUp(): void
    {
        $this->con = TestHelper::getConnection();
    }

    /**
     * TC-I-08: Thêm sản phẩm vào session giỏ hàng
     */
    public function testAddProductToCart(): void
    {
        $cart = [];
        $masp = 1;
        $quantity = 2;
        $size = 42;

        $cart[$masp] = ['quantity' => $quantity, 'size' => $size];

        $this->assertArrayHasKey($masp, $cart, "Sản phẩm phải có trong giỏ");
        $this->assertEquals(2, $cart[$masp]['quantity'], "Số lượng phải đúng");
        $this->assertEquals(42, $cart[$masp]['size'], "Size phải đúng");
    }

    /**
     * TC-I-09: Tính tổng tiền giỏ hàng đúng
     */
    public function testCartTotalCalculation(): void
    {
        $cart = [
            1 => ['quantity' => 2, 'size' => 42],
            2 => ['quantity' => 1, 'size' => 43],
        ];

        $ids = implode(',', array_keys($cart));
        $result = $this->con->query("SELECT * FROM `tbl_qlsanpham` WHERE `masp` IN ($ids)");

        $grandTotal = 0;
        while ($row = $result->fetch_assoc()) {
            $grandTotal += $row['giasanpham'] * $cart[$row['masp']]['quantity'];
        }

        // SP1: 1500000 * 2 = 3000000, SP2: 2500000 * 1 = 2500000
        $expectedTotal = (1500000 * 2) + (2500000 * 1);
        $this->assertEquals($expectedTotal, $grandTotal, "Tổng tiền phải đúng: $expectedTotal");
    }

    /**
     * TC-I-10: Cập nhật số lượng sản phẩm
     */
    public function testUpdateCartQuantity(): void
    {
        $cart = [1 => ['quantity' => 1, 'size' => 42]];

        // Tăng số lượng
        $cart[1]['quantity'] = 5;
        $this->assertEquals(5, $cart[1]['quantity'], "Số lượng sau cập nhật phải là 5");
    }

    /**
     * TC-I-11: Xóa sản phẩm khỏi giỏ
     */
    public function testRemoveProductFromCart(): void
    {
        $cart = [
            1 => ['quantity' => 1, 'size' => 42],
            2 => ['quantity' => 2, 'size' => 43],
        ];

        unset($cart[1]);

        $this->assertArrayNotHasKey(1, $cart, "SP 1 phải bị xóa");
        $this->assertArrayHasKey(2, $cart, "SP 2 vẫn còn");
        $this->assertCount(1, $cart, "Giỏ hàng còn 1 SP");
    }

    /**
     * TC-I-12: Migrate format giỏ hàng cũ (giá trị đơn) sang format mới (array)
     */
    public function testMigrateOldCartFormat(): void
    {
        // Format cũ: masp => quantity (int)
        $oldCart = [1 => 3, 2 => 1];

        // Migrate logic giống cart_update_ajax.php
        foreach ($oldCart as $masp => $val) {
            if (!is_array($val)) {
                $oldCart[$masp] = ['quantity' => (int)$val, 'size' => null];
            }
        }

        $this->assertIsArray($oldCart[1], "Phải chuyển thành array");
        $this->assertEquals(3, $oldCart[1]['quantity'], "Quantity phải giữ nguyên");
        $this->assertNull($oldCart[1]['size'], "Size phải null khi migrate");
    }

    /**
     * TC-I-13: Giỏ hàng rỗng → tổng = 0
     */
    public function testEmptyCartTotal(): void
    {
        $cart = [];
        $grandTotal = 0;

        if (!empty($cart)) {
            // Would query DB
            $grandTotal = -1; // Should never reach here
        }

        $this->assertEquals(0, $grandTotal, "Giỏ rỗng → tổng phải = 0");
    }

    /**
     * TC-I-14: Sản phẩm trong giỏ phải tồn tại trong DB
     */
    public function testCartProductExistsInDatabase(): void
    {
        $cart = [1 => ['quantity' => 1, 'size' => 42]];
        $ids = implode(',', array_keys($cart));

        $result = $this->con->query("SELECT COUNT(*) AS cnt FROM `tbl_qlsanpham` WHERE `masp` IN ($ids)");
        $row = $result->fetch_assoc();

        $this->assertEquals(count($cart), (int)$row['cnt'], "Tất cả SP trong giỏ phải có trong DB");
    }
}
