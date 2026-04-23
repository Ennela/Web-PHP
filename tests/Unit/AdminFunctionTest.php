<?php
/**
 * UNIT TEST: Admin Functions
 * Test validateDateTime(), validateUploadFile()
 */

use PHPUnit\Framework\TestCase;

class AdminFunctionTest extends TestCase
{
    protected function setUp(): void
    {
        require_once BASE_PATH . 'admin/function.php';
    }

    // ================= validateDateTime() =================

    /**
     * TC-U-07: Ngày hợp lệ DD-MM-YYYY
     */
    public function testValidDateTimeWithValidDate(): void
    {
        $this->assertTrue(validateDateTime('15-06-2024'), "15-06-2024 phải hợp lệ");
        $this->assertTrue(validateDateTime('01-01-2025'), "01-01-2025 phải hợp lệ");
        $this->assertTrue(validateDateTime('31-12-2024'), "31-12-2024 phải hợp lệ");
    }

    /**
     * TC-U-08: Năm nhuận - 29/02 hợp lệ
     */
    public function testValidDateTimeLeapYear(): void
    {
        $this->assertTrue(validateDateTime('29-02-2024'), "29-02-2024 (năm nhuận) phải hợp lệ");
    }

    /**
     * TC-U-09: Năm không nhuận - 29/02 không hợp lệ
     */
    public function testValidDateTimeNonLeapYear(): void
    {
        $this->assertFalse(validateDateTime('29-02-2023'), "29-02-2023 (không nhuận) phải không hợp lệ");
    }

    /**
     * TC-U-10: Tháng 30 ngày - ngày 31 không hợp lệ
     */
    public function testValidDateTimeDay31InMonth30(): void
    {
        $this->assertFalse(validateDateTime('31-04-2024'), "31-04-2024 phải không hợp lệ (tháng 4 chỉ 30 ngày)");
        $this->assertFalse(validateDateTime('31-06-2024'), "31-06-2024 phải không hợp lệ");
        $this->assertFalse(validateDateTime('31-09-2024'), "31-09-2024 phải không hợp lệ");
        $this->assertFalse(validateDateTime('31-11-2024'), "31-11-2024 phải không hợp lệ");
    }

    /**
     * TC-U-11: Định dạng sai hoàn toàn
     */
    public function testValidDateTimeInvalidFormat(): void
    {
        $this->assertFalse(validateDateTime('2024/01/01'), "Format sai phải trả về false");
        $this->assertFalse(validateDateTime('abc'), "Chuỗi bất kỳ phải trả về false");
        $this->assertFalse(validateDateTime(''), "Chuỗi rỗng phải trả về false");
        $this->assertFalse(validateDateTime('01/01/2024'), "Format dấu / phải trả về false");
    }

    /**
     * TC-U-12: Tháng 31 ngày - ngày 31 hợp lệ
     */
    public function testValidDateTimeDay31InMonth31(): void
    {
        $this->assertTrue(validateDateTime('31-01-2024'), "31-01 phải hợp lệ");
        $this->assertTrue(validateDateTime('31-03-2024'), "31-03 phải hợp lệ");
        $this->assertTrue(validateDateTime('31-05-2024'), "31-05 phải hợp lệ");
        $this->assertTrue(validateDateTime('31-07-2024'), "31-07 phải hợp lệ");
        $this->assertTrue(validateDateTime('31-08-2024'), "31-08 phải hợp lệ");
        $this->assertTrue(validateDateTime('31-10-2024'), "31-10 phải hợp lệ");
        $this->assertTrue(validateDateTime('31-12-2024'), "31-12 phải hợp lệ");
    }

    // ================= validateUploadFile() =================

    /**
     * TC-U-13: File JPG hợp lệ dưới 2MB
     */
    public function testValidUploadFileJpg(): void
    {
        $file = [
            'name' => 'product.jpg',
            'size' => 500000, // 500KB
            'tmp_name' => '/tmp/test.jpg',
        ];
        $result = validateUploadFile($file, 'uploads');
        $this->assertIsArray($result, "File JPG hợp lệ phải trả về array");
        $this->assertEquals('product.jpg', $result['name']);
    }

    /**
     * TC-U-14: File PNG hợp lệ
     */
    public function testValidUploadFilePng(): void
    {
        $file = [
            'name' => 'image.png',
            'size' => 1000000, // 1MB
            'tmp_name' => '/tmp/test.png',
        ];
        $result = validateUploadFile($file, 'uploads');
        $this->assertIsArray($result, "File PNG hợp lệ phải trả về array");
    }

    /**
     * TC-U-15: File vượt 2MB → reject
     */
    public function testValidUploadFileExceedsMaxSize(): void
    {
        $file = [
            'name' => 'huge.jpg',
            'size' => 3 * 1024 * 1024, // 3MB
            'tmp_name' => '/tmp/huge.jpg',
        ];
        $result = validateUploadFile($file, 'uploads');
        $this->assertFalse($result, "File > 2MB phải bị reject");
    }

    /**
     * TC-U-16: File không hợp lệ (.exe, .php)
     */
    public function testValidUploadFileInvalidType(): void
    {
        $files = [
            ['name' => 'malware.exe', 'size' => 100, 'tmp_name' => '/tmp/test.exe'],
            ['name' => 'script.php', 'size' => 100, 'tmp_name' => '/tmp/test.php'],
            ['name' => 'code.js', 'size' => 100, 'tmp_name' => '/tmp/test.js'],
            ['name' => 'page.html', 'size' => 100, 'tmp_name' => '/tmp/test.html'],
        ];

        foreach ($files as $file) {
            $result = validateUploadFile($file, 'uploads');
            $this->assertFalse($result, "File {$file['name']} phải bị reject");
        }
    }

    /**
     * TC-U-17: File BMP và XLSX hợp lệ
     */
    public function testValidUploadFileBmpAndXlsx(): void
    {
        $bmp = ['name' => 'image.bmp', 'size' => 500000, 'tmp_name' => '/tmp/test.bmp'];
        $xlsx = ['name' => 'data.xlsx', 'size' => 500000, 'tmp_name' => '/tmp/test.xlsx'];

        $this->assertIsArray(validateUploadFile($bmp, 'uploads'), "BMP phải hợp lệ");
        $this->assertIsArray(validateUploadFile($xlsx, 'uploads'), "XLSX phải hợp lệ");
    }
}
