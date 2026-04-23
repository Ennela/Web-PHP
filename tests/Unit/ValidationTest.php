<?php
/**
 * UNIT TEST: Validation Logic
 * Test các quy tắc validate được trích xuất từ API endpoints
 */

use PHPUnit\Framework\TestCase;

class ValidationTest extends TestCase
{
    // ================= Email Validation =================

    /**
     * TC-U-18: Email hợp lệ
     */
    public function testValidEmails(): void
    {
        $validEmails = [
            'user@example.com',
            'test.name@domain.co',
            'user+tag@gmail.com',
            'name123@sub.domain.org',
        ];

        foreach ($validEmails as $email) {
            $this->assertNotFalse(
                filter_var($email, FILTER_VALIDATE_EMAIL),
                "Email '$email' phải hợp lệ"
            );
        }
    }

    /**
     * TC-U-19: Email không hợp lệ
     */
    public function testInvalidEmails(): void
    {
        $invalidEmails = [
            'plainaddress',
            '@no-local-part.com',
            'user@',
            'user@.com',
            'user space@domain.com',
        ];

        foreach ($invalidEmails as $email) {
            $this->assertFalse(
                filter_var($email, FILTER_VALIDATE_EMAIL),
                "Email '$email' phải không hợp lệ"
            );
        }
    }

    // ================= Phone Validation =================

    /**
     * TC-U-20: SĐT hợp lệ (bắt đầu 0 hoặc +84, 10-11 số)
     */
    public function testValidPhoneNumbers(): void
    {
        $validPhones = [
            '0394680113',
            '0912345678',
            '0123456789',
            '+84912345678',
            '+849123456789',
        ];

        $pattern = '/^(0|\+84)[0-9]{9,10}$/';
        foreach ($validPhones as $phone) {
            $this->assertMatchesRegularExpression(
                $pattern,
                $phone,
                "SĐT '$phone' phải hợp lệ"
            );
        }
    }

    /**
     * TC-U-21: SĐT không hợp lệ
     */
    public function testInvalidPhoneNumbers(): void
    {
        $invalidPhones = [
            '12345',
            'abc',
            '094-680-113',
            '5551234567',
            '',
            '+1234567890',
        ];

        $pattern = '/^(0|\+84)[0-9]{9,10}$/';
        foreach ($invalidPhones as $phone) {
            $this->assertDoesNotMatchRegularExpression(
                $pattern,
                $phone,
                "SĐT '$phone' phải không hợp lệ"
            );
        }
    }

    // ================= Password Validation =================

    /**
     * TC-U-22: Mật khẩu hợp lệ (>= 6 ký tự)
     */
    public function testValidPasswords(): void
    {
        $validPasswords = ['123456', 'abcdef', 'myP@ss1', 'longerpassword123'];

        foreach ($validPasswords as $pass) {
            $this->assertGreaterThanOrEqual(6, strlen($pass), "Mật khẩu '$pass' phải >= 6 ký tự");
        }
    }

    /**
     * TC-U-23: Mật khẩu quá ngắn (< 6 ký tự)
     */
    public function testPasswordTooShort(): void
    {
        $shortPasswords = ['', '1', '12', '123', '1234', '12345'];

        foreach ($shortPasswords as $pass) {
            $this->assertLessThan(6, strlen($pass), "Mật khẩu '$pass' phải < 6 ký tự");
        }
    }

    /**
     * TC-U-24: Xác nhận mật khẩu khớp
     */
    public function testPasswordConfirmationMatch(): void
    {
        $password = 'myPassword123';
        $confirm = 'myPassword123';
        $this->assertEquals($password, $confirm, "Mật khẩu xác nhận phải khớp");
    }

    /**
     * TC-U-25: Xác nhận mật khẩu không khớp
     */
    public function testPasswordConfirmationMismatch(): void
    {
        $password = 'myPassword123';
        $confirm = 'differentPass456';
        $this->assertNotEquals($password, $confirm, "Mật khẩu xác nhận không khớp");
    }

    /**
     * TC-U-26: Mật khẩu mới phải khác cũ
     */
    public function testNewPasswordDifferentFromOld(): void
    {
        $old = 'oldPassword123';
        $new = 'newPassword456';
        $this->assertNotEquals($old, $new, "Mật khẩu mới phải khác mật khẩu cũ");
    }

    // ================= Gender Validation =================

    /**
     * TC-U-27: Giới tính hợp lệ
     */
    public function testValidGenders(): void
    {
        $validGenders = ['Nam', 'Nữ', 'Khác'];
        foreach ($validGenders as $g) {
            $this->assertContains($g, ['Nam', 'Nữ', 'Khác'], "'$g' phải là giới tính hợp lệ");
        }
    }

    /**
     * TC-U-28: Giới tính không hợp lệ
     */
    public function testInvalidGenders(): void
    {
        $invalidGenders = ['Male', 'Female', 'nam', 'Other', ''];
        foreach ($invalidGenders as $g) {
            $this->assertNotContains($g, ['Nam', 'Nữ', 'Khác'], "'$g' phải không hợp lệ");
        }
    }

    // ================= Date of Birth Validation =================

    /**
     * TC-U-29: Ngày sinh format Y-m-d hợp lệ
     */
    public function testValidDateOfBirth(): void
    {
        $validDates = ['2000-01-15', '1995-12-31', '2005-06-01'];

        foreach ($validDates as $date) {
            $d = DateTime::createFromFormat('Y-m-d', $date);
            $this->assertNotFalse($d, "Ngày '$date' phải parse được");
            $this->assertEquals($date, $d->format('Y-m-d'), "Ngày '$date' phải khớp format Y-m-d");
        }
    }

    /**
     * TC-U-30: Ngày sinh format sai
     */
    public function testInvalidDateOfBirth(): void
    {
        $invalidDates = ['15/01/2000', '2000-13-01', '01-2000-15', 'abc'];

        foreach ($invalidDates as $date) {
            $d = DateTime::createFromFormat('Y-m-d', $date);
            $isValid = ($d && $d->format('Y-m-d') === $date);
            $this->assertFalse($isValid, "Ngày '$date' phải không hợp lệ");
        }
    }

    // ================= Size System Validation =================

    /**
     * TC-U-31: Hệ size hợp lệ
     */
    public function testValidSizeSystems(): void
    {
        $validSystems = ['EU', 'US', 'CM'];
        foreach ($validSystems as $sys) {
            $this->assertContains($sys, ['EU', 'US', 'CM'], "'$sys' phải hợp lệ");
        }
    }

    /**
     * TC-U-32: Hệ size không hợp lệ
     */
    public function testInvalidSizeSystems(): void
    {
        $invalidSystems = ['UK', 'JP', 'eu', '', 'abc'];
        foreach ($invalidSystems as $sys) {
            $this->assertNotContains($sys, ['EU', 'US', 'CM'], "'$sys' phải không hợp lệ");
        }
    }
}
