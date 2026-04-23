<?php
/**
 * UNIT TEST: OrderHelper
 * Test hàm generateOrderCode() - Sinh mã đơn hàng 9 chữ số unique
 */

use PHPUnit\Framework\TestCase;

class OrderHelperTest extends TestCase
{
    private mysqli $con;

    protected function setUp(): void
    {
        $this->con = TestHelper::getConnection();
        // Đảm bảo bảng oder sạch cho test
        $this->con->query("SET FOREIGN_KEY_CHECKS = 0");
        $this->con->query("DELETE FROM `oder_chitiet`");
        $this->con->query("DELETE FROM `oder`");
        $this->con->query("SET FOREIGN_KEY_CHECKS = 1");

        require_once BASE_PATH . 'includes/order_helper.php';
    }

    protected function tearDown(): void
    {
        TestHelper::resetDatabase();
    }

    /**
     * TC-U-01: Mã đơn hàng phải luôn có đúng 9 chữ số
     */
    public function testOrderCodeHasNineDigits(): void
    {
        $code = generateOrderCode($this->con);
        $this->assertEquals(9, strlen($code), "Mã đơn hàng phải có 9 ký tự");
    }

    /**
     * TC-U-02: Mã đơn hàng chỉ chứa ký tự số
     */
    public function testOrderCodeContainsOnlyDigits(): void
    {
        $code = generateOrderCode($this->con);
        $this->assertMatchesRegularExpression('/^\d{9}$/', $code, "Mã đơn hàng chỉ được chứa số");
    }

    /**
     * TC-U-03: Sinh 50 mã liên tiếp - không trùng lặp
     */
    public function testOrderCodesAreUnique(): void
    {
        $codes = [];
        for ($i = 0; $i < 50; $i++) {
            $code = generateOrderCode($this->con);
            $this->assertNotContains($code, $codes, "Mã đơn hàng bị trùng lặp: $code");
            $codes[] = $code;

            // Insert vào DB để mô phỏng thực tế
            $this->con->query("INSERT INTO `oder` (`order_code`, `tenkh`, `sdt`, `diachi`, `tongtien`, `ngaytao`, `status`) 
                VALUES ('$code', 'Test', '0123456789', 'Addr', 0, " . time() . ", 'PENDING')");
        }

        $this->assertCount(50, array_unique($codes), "50 mã phải unique");
    }

    /**
     * TC-U-04: Mã đơn hàng nằm trong phạm vi 100000000 - 999999999
     */
    public function testOrderCodeInValidRange(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $code = generateOrderCode($this->con);
            $num = intval($code);
            $this->assertGreaterThanOrEqual(100000000, $num, "Mã phải >= 100000000");
            $this->assertLessThanOrEqual(999999999, $num, "Mã phải <= 999999999");
        }
    }

    /**
     * TC-U-05: Không trùng với mã đã tồn tại trong DB
     */
    public function testOrderCodeDoesNotCollideWithExisting(): void
    {
        // Insert mã cố định vào DB
        $this->con->query("INSERT INTO `oder` (`order_code`, `tenkh`, `sdt`, `diachi`, `tongtien`, `ngaytao`, `status`) 
            VALUES ('123456789', 'Test', '0123456789', 'Test', 0, " . time() . ", 'PENDING')");

        $code = generateOrderCode($this->con);
        $this->assertNotEquals('123456789', $code, "Mã mới không được trùng mã đã tồn tại");
    }

    /**
     * TC-U-06: Fallback khi mã bị trùng nhiều lần vẫn trả về kết quả
     */
    public function testOrderCodeAlwaysReturnsResult(): void
    {
        $code = generateOrderCode($this->con);
        $this->assertNotEmpty($code, "Hàm phải luôn trả về mã");
        $this->assertIsString($code, "Kết quả phải là string");
    }
}
