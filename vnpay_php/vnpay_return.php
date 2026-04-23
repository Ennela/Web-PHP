<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kết quả thanh toán VNPAY</title>
    <!-- Modern Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        .glass-panel {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.4);
        }
        .success-grad { background: linear-gradient(135deg, #10B981 0%, #059669 100%); }
        .error-grad { background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%); }
        .animate-pop-in { animation: popIn 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
        @keyframes popIn {
            0% { opacity: 0; transform: scale(0.9) translateY(20px); }
            100% { opacity: 1; transform: scale(1) translateY(0); }
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">

    <div class="max-w-md w-full animate-pop-in">
        <div class="glass-panel shadow-2xl rounded-3xl overflow-hidden relative">
            
            <?php
            require_once("config.php");
            $vnp_SecureHash = $_GET['vnp_SecureHash'] ?? '';
            $inputData = array();
            foreach ($_GET as $key => $value) {
                if (substr($key, 0, 4) == "vnp_") {
                    $inputData[$key] = $value;
                }
            }
            if(isset($inputData['vnp_SecureHash'])) unset($inputData['vnp_SecureHash']);
            ksort($inputData);
            $i = 0;
            $hashData = "";
            foreach ($inputData as $key => $value) {
                if ($i == 1) {
                    $hashData = $hashData . '&' . urlencode($key) . "=" . urlencode($value);
                } else {
                    $hashData = $hashData . urlencode($key) . "=" . urlencode($value);
                    $i = 1;
                }
            }
            $secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);
            
            $isSuccess = ($secureHash == $vnp_SecureHash && $_GET['vnp_ResponseCode'] == '00');
            $headerClass = $isSuccess ? 'success-grad' : 'error-grad';
            $icon = $isSuccess ? 
                '<svg class="w-16 h-16 text-white mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>' : 
                '<svg class="w-16 h-16 text-white mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>';
            $title = $isSuccess ? 'Thanh Toán Thành Công!' : 'Giao Dịch Thất Bại';
            ?>

            <!-- Header -->
            <div class="<?= $headerClass ?> px-6 py-8 text-center">
                <div class="mb-4">
                    <?= $icon ?>
                </div>
                <h2 class="text-2xl font-bold text-white"><?= $title ?></h2>
                <p class="text-white text-opacity-80 mt-1">Đơn hàng số: #<?= htmlspecialchars($_GET['vnp_TxnRef'] ?? 'N/A') ?></p>
            </div>

            <!-- Content -->
            <div class="px-8 py-6">
                
                <div class="space-y-4 mb-8">
                    <div class="flex flex-col">
                        <span class="text-sm text-gray-500 font-medium">Số tiền thanh toán</span>
                        <span class="text-xl font-bold text-gray-900"><?= number_format(($_GET['vnp_Amount'] ?? 0) / 100, 0, ',', '.') ?> VND</span>
                    </div>

                    <div class="border-t border-dashed border-gray-200 my-2"></div>

                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-500">Mã giao dịch VNPAY</span>
                        <span class="text-sm font-semibold text-gray-800"><?= htmlspecialchars($_GET['vnp_TransactionNo'] ?? 'N/A') ?></span>
                    </div>

                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-500">Mã ngân hàng</span>
                        <span class="text-sm font-semibold text-gray-800"><?= htmlspecialchars($_GET['vnp_BankCode'] ?? 'N/A') ?></span>
                    </div>

                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-500">Thời gian</span>
                        <span class="text-sm font-semibold text-gray-800">
                            <?php 
                                $dateStr = $_GET['vnp_PayDate'] ?? '';
                                if(strlen($dateStr) == 14) {
                                    echo date('d/m/Y H:i:s', strtotime($dateStr));
                                } else {
                                    echo date('d/m/Y H:i:s');
                                }
                            ?>
                        </span>
                    </div>

                    <?php if($secureHash != $vnp_SecureHash): ?>
                    <div class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                        <p class="text-xs text-red-600 font-medium text-center">Cảnh báo: Chữ ký không hợp lệ, dữ liệu có thể đã bị thay đổi!</p>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="flex flex-col gap-3">
                    <a href="../trangchu.php" class="w-full text-center bg-gray-900 hover:bg-gray-800 text-white font-semibold py-3 px-4 rounded-xl transition duration-200 shadow-md">
                        Tiếp tục mua sắm
                    </a>
                    <a href="../giohang.php" class="w-full text-center bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 font-semibold py-3 px-4 rounded-xl transition duration-200">
                        Quay về giỏ hàng
                    </a>
                </div>
            </div>

            <!-- Footer Badge -->
            <div class="bg-gray-50 py-3 text-center border-t border-gray-100">
                <span class="text-xs text-gray-400 font-medium">Bảo mật bởi VNPAY Secure</span>
            </div>
            
        </div>
    </div>

</body>
</html>