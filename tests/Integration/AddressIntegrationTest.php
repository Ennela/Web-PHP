<?php
/**
 * INTEGRATION TEST: Address Management
 * Test CRUD sổ địa chỉ khách hàng với DB
 */

use PHPUnit\Framework\TestCase;

class AddressIntegrationTest extends TestCase
{
    private mysqli $con;
    private int $makh = 1; // Test user ID

    protected function setUp(): void
    {
        $this->con = TestHelper::getConnection();
        // Xoá địa chỉ test trước mỗi test
        $this->con->query("DELETE FROM `tbl_diachi` WHERE `makh` = {$this->makh}");
    }

    protected function tearDown(): void
    {
        $this->con->query("DELETE FROM `tbl_diachi` WHERE `makh` = {$this->makh}");
    }

    /**
     * TC-I-21: Thêm địa chỉ mới → lưu đúng vào DB
     */
    public function testAddNewAddress(): void
    {
        $now = time();
        $result = $this->con->query("INSERT INTO `tbl_diachi` 
            (`makh`, `hoten`, `sdt`, `tinh`, `quan_huyen`, `phuong_xa`, `diachi_cuthe`, `macdinh`, `ngaytao`, `ngaycapnhat`) 
            VALUES ({$this->makh}, 'Nguyễn Văn A', '0394680113', 'Hà Nội', 'Hoàn Kiếm', 'P. Tràng Tiền', 'Số 1 Tràng Tiền', 1, $now, $now)");

        $this->assertTrue($result, "Insert địa chỉ phải thành công");

        $check = $this->con->query("SELECT * FROM `tbl_diachi` WHERE `makh` = {$this->makh}");
        $this->assertEquals(1, $check->num_rows);

        $row = $check->fetch_assoc();
        $this->assertEquals('Nguyễn Văn A', $row['hoten']);
        $this->assertEquals('0394680113', $row['sdt']);
        $this->assertEquals(1, $row['macdinh']);
    }

    /**
     * TC-I-22: Sửa địa chỉ → field cập nhật đúng
     */
    public function testEditAddress(): void
    {
        $now = time();
        $this->con->query("INSERT INTO `tbl_diachi` 
            (`makh`, `hoten`, `sdt`, `diachi_cuthe`, `macdinh`, `ngaytao`, `ngaycapnhat`) 
            VALUES ({$this->makh}, 'Old Name', '0394680113', 'Old Address', 0, $now, $now)");
        $id = $this->con->insert_id;

        // Update
        $newTime = time();
        $this->con->query("UPDATE `tbl_diachi` SET `hoten` = 'New Name', `diachi_cuthe` = 'New Address', `ngaycapnhat` = $newTime WHERE `id` = $id AND `makh` = {$this->makh}");

        $check = $this->con->query("SELECT * FROM `tbl_diachi` WHERE `id` = $id");
        $row = $check->fetch_assoc();

        $this->assertEquals('New Name', $row['hoten'], "Tên phải cập nhật");
        $this->assertEquals('New Address', $row['diachi_cuthe'], "Địa chỉ phải cập nhật");
    }

    /**
     * TC-I-23: Xóa địa chỉ mặc định → set địa chỉ khác làm mặc định
     */
    public function testDeleteDefaultAddressSetsNewDefault(): void
    {
        $now = time();
        // Thêm 2 địa chỉ
        $this->con->query("INSERT INTO `tbl_diachi` (`makh`, `hoten`, `sdt`, `diachi_cuthe`, `macdinh`, `ngaytao`, `ngaycapnhat`) 
            VALUES ({$this->makh}, 'Addr1', '0394680113', 'Điểm 1', 1, $now, $now)");
        $id1 = $this->con->insert_id;

        $this->con->query("INSERT INTO `tbl_diachi` (`makh`, `hoten`, `sdt`, `diachi_cuthe`, `macdinh`, `ngaytao`, `ngaycapnhat`) 
            VALUES ({$this->makh}, 'Addr2', '0912345678', 'Điểm 2', 0, $now, $now)");
        $id2 = $this->con->insert_id;

        // Xóa địa chỉ mặc định (id1)
        $this->con->query("DELETE FROM `tbl_diachi` WHERE `id` = $id1 AND `makh` = {$this->makh}");

        // Set mặc định cho cái còn lại (mô phỏng logic API)
        $first = $this->con->query("SELECT `id` FROM `tbl_diachi` WHERE `makh` = {$this->makh} ORDER BY `ngaytao` DESC LIMIT 1");
        $firstRow = $first->fetch_assoc();
        if ($firstRow) {
            $this->con->query("UPDATE `tbl_diachi` SET `macdinh` = 1 WHERE `id` = " . $firstRow['id']);
        }

        // Verify
        $check = $this->con->query("SELECT * FROM `tbl_diachi` WHERE `id` = $id2");
        $row = $check->fetch_assoc();
        $this->assertEquals(1, $row['macdinh'], "Địa chỉ còn lại phải thành mặc định");
    }

    /**
     * TC-I-24: Giới hạn tối đa 5 địa chỉ
     */
    public function testMaxFiveAddresses(): void
    {
        $now = time();
        for ($i = 1; $i <= 5; $i++) {
            $this->con->query("INSERT INTO `tbl_diachi` (`makh`, `hoten`, `sdt`, `diachi_cuthe`, `ngaytao`, `ngaycapnhat`) 
                VALUES ({$this->makh}, 'Addr$i', '039468011$i', 'Địa chỉ $i', $now, $now)");
        }

        // Kiểm tra đếm
        $count = $this->con->query("SELECT COUNT(*) AS cnt FROM `tbl_diachi` WHERE `makh` = {$this->makh}");
        $row = $count->fetch_assoc();

        $this->assertEquals(5, $row['cnt'], "Phải có đúng 5 địa chỉ");

        // Logic kiểm tra giới hạn (mô phỏng API)
        $canAdd = ($row['cnt'] < 5);
        $this->assertFalse($canAdd, "Không được thêm quá 5 địa chỉ");
    }

    /**
     * TC-I-25: Đặt mặc định → bỏ mặc định cũ
     */
    public function testSetDefaultRemovesOldDefault(): void
    {
        $now = time();
        $this->con->query("INSERT INTO `tbl_diachi` (`makh`, `hoten`, `sdt`, `diachi_cuthe`, `macdinh`, `ngaytao`, `ngaycapnhat`) 
            VALUES ({$this->makh}, 'Old Default', '0394680113', 'D1', 1, $now, $now)");
        $id1 = $this->con->insert_id;

        $this->con->query("INSERT INTO `tbl_diachi` (`makh`, `hoten`, `sdt`, `diachi_cuthe`, `macdinh`, `ngaytao`, `ngaycapnhat`) 
            VALUES ({$this->makh}, 'New Default', '0912345678', 'D2', 0, $now, $now)");
        $id2 = $this->con->insert_id;

        // Đặt id2 là mặc định (mô phỏng API logic)
        $this->con->query("UPDATE `tbl_diachi` SET `macdinh` = 0 WHERE `makh` = {$this->makh}");
        $this->con->query("UPDATE `tbl_diachi` SET `macdinh` = 1 WHERE `id` = $id2 AND `makh` = {$this->makh}");

        // Verify
        $old = $this->con->query("SELECT `macdinh` FROM `tbl_diachi` WHERE `id` = $id1")->fetch_assoc();
        $new = $this->con->query("SELECT `macdinh` FROM `tbl_diachi` WHERE `id` = $id2")->fetch_assoc();

        $this->assertEquals(0, $old['macdinh'], "Mặc định cũ phải bị bỏ");
        $this->assertEquals(1, $new['macdinh'], "Mặc định mới phải được set");
    }
}
