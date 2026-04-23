<?php
/**
 * INTEGRATION TEST: Order
 * Test luồng đặt hàng COD với DB thực
 */

use PHPUnit\Framework\TestCase;

class OrderIntegrationTest extends TestCase
{
    private mysqli $con;

    protected function setUp(): void
    {
        $this->con = TestHelper::getConnection();
        require_once BASE_PATH . 'includes/order_helper.php';
    }

    protected function tearDown(): void
    {
        // Xoá đơn hàng test tạm
        $this->con->query("SET FOREIGN_KEY_CHECKS = 0");
        $this->con->query("DELETE FROM `oder_chitiet` WHERE `madonhang` NOT IN (1, 2)");
        $this->con->query("DELETE FROM `oder` WHERE `id` NOT IN (1, 2)");
        $this->con->query("SET FOREIGN_KEY_CHECKS = 1");
    }

    /**
     * TC-I-15: Tạo đơn hàng COD → lưu đúng vào DB
     */
    public function testCreateCodOrder(): void
    {
        $orderCode = generateOrderCode($this->con);
        $now = time();

        $result = $this->con->query("INSERT INTO `oder` 
            (`order_code`, `tenkh`, `sdt`, `diachi`, `note`, `tongtien`, `ngaytao`, `status`, `makh`) 
            VALUES ('$orderCode', 'Khách Test COD', '0394680113', 'Hà Nội', 'Ghi chú test', 1500000, $now, 'PENDING', 1)");

        $this->assertTrue($result, "Insert đơn hàng phải thành công");

        $orderId = $this->con->insert_id;
        $this->assertGreaterThan(0, $orderId, "Order ID phải > 0");

        // Verify dữ liệu
        $check = $this->con->query("SELECT * FROM `oder` WHERE `id` = $orderId");
        $row = $check->fetch_assoc();

        $this->assertEquals('Khách Test COD', $row['tenkh']);
        $this->assertEquals('PENDING', $row['status']);
        $this->assertEquals(1500000, $row['tongtien']);
        $this->assertEquals($orderCode, $row['order_code']);
    }

    /**
     * TC-I-16: Chi tiết đơn hàng liên kết đúng sản phẩm
     */
    public function testOrderDetailLinksToProduct(): void
    {
        // Tạo đơn hàng
        $orderCode = generateOrderCode($this->con);
        $now = time();
        $this->con->query("INSERT INTO `oder` 
            (`order_code`, `tenkh`, `sdt`, `diachi`, `tongtien`, `ngaytao`, `status`) 
            VALUES ('$orderCode', 'Test', '0394680113', 'HN', 1500000, $now, 'PENDING')");
        $orderId = $this->con->insert_id;

        // Thêm chi tiết
        $result = $this->con->query("INSERT INTO `oder_chitiet` 
            (`madonhang`, `masp`, `quantity`, `size`, `price`, `created_time`, `last_updated`) 
            VALUES ($orderId, 1, 2, 42, 1500000, $now, $now)");

        $this->assertTrue($result, "Insert chi tiết đơn hàng phải thành công");

        // Verify join
        $check = $this->con->query("SELECT oc.*, sp.tensp FROM `oder_chitiet` oc 
            JOIN `tbl_qlsanpham` sp ON oc.masp = sp.masp 
            WHERE oc.madonhang = $orderId");

        $this->assertEquals(1, $check->num_rows, "Phải có 1 dòng chi tiết");
        $row = $check->fetch_assoc();
        $this->assertEquals(2, $row['quantity']);
        $this->assertEquals(42, $row['size']);
        $this->assertEquals('Giày Test Nike Air Force 1', $row['tensp']);
    }

    /**
     * TC-I-17: Order code phải unique trong DB (UNIQUE constraint)
     */
    public function testOrderCodeUniqueConstraint(): void
    {
        $this->expectException(\mysqli_sql_exception::class);
        $this->con->query("INSERT INTO `oder` 
            (`order_code`, `tenkh`, `sdt`, `diachi`, `tongtien`, `ngaytao`, `status`) 
            VALUES ('123456789', 'Dup Test', '0123456789', 'Addr', 0, " . time() . ", 'PENDING')");
    }

    /**
     * TC-I-18: Cascade delete - xóa đơn hàng → xóa chi tiết
     */
    public function testCascadeDeleteOrderDetails(): void
    {
        $orderCode = generateOrderCode($this->con);
        $now = time();
        $this->con->query("INSERT INTO `oder` 
            (`order_code`, `tenkh`, `sdt`, `diachi`, `tongtien`, `ngaytao`, `status`) 
            VALUES ('$orderCode', 'Del Test', '0123', 'Addr', 0, $now, 'PENDING')");
        $orderId = $this->con->insert_id;

        $this->con->query("INSERT INTO `oder_chitiet` 
            (`madonhang`, `masp`, `quantity`, `price`, `created_time`, `last_updated`) 
            VALUES ($orderId, 1, 1, 1500000, $now, $now)");

        // Xóa đơn hàng
        $this->con->query("DELETE FROM `oder` WHERE `id` = $orderId");

        // Kiểm tra chi tiết cũng bị xóa
        $check = $this->con->query("SELECT COUNT(*) AS cnt FROM `oder_chitiet` WHERE `madonhang` = $orderId");
        $row = $check->fetch_assoc();
        $this->assertEquals(0, $row['cnt'], "Chi tiết phải bị cascade delete");
    }

    /**
     * TC-I-19: Cập nhật trạng thái đơn hàng
     */
    public function testUpdateOrderStatus(): void
    {
        $this->con->query("UPDATE `oder` SET `status` = 'CONFIRMED' WHERE `id` = 1");

        $check = $this->con->query("SELECT `status` FROM `oder` WHERE `id` = 1");
        $row = $check->fetch_assoc();

        $this->assertEquals('CONFIRMED', $row['status'], "Status phải được cập nhật");

        // Restore
        $this->con->query("UPDATE `oder` SET `status` = 'PENDING' WHERE `id` = 1");
    }

    /**
     * TC-I-20: Tính doanh thu đơn hàng DELIVERED
     */
    public function testCalculateRevenue(): void
    {
        $result = $this->con->query("SELECT SUM(`tongtien`) AS revenue FROM `oder` WHERE `status` = 'DELIVERED'");
        $row = $result->fetch_assoc();

        $this->assertGreaterThanOrEqual(0, (int)$row['revenue'], "Doanh thu phải >= 0");
    }
}
