<?php
require_once("./config.php");
require_once("../connect1.php");

$inputData = array();
$returnData = array();
foreach ($_GET as $key => $value) {
    if (substr($key, 0, 4) == "vnp_") {
        $inputData[$key] = $value;
    }
}

$vnp_SecureHash = $inputData['vnp_SecureHash'];
unset($inputData['vnp_SecureHash']);
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
$vnpTranId = $inputData['vnp_TransactionNo']; 
$vnp_BankCode = $inputData['vnp_BankCode']; 
$vnp_Amount = $inputData['vnp_Amount']/100; 

$orderId = (int)$inputData['vnp_TxnRef'];

try {
    if ($secureHash == $vnp_SecureHash) {
        $query = mysqli_query($con, "SELECT * FROM oder WHERE id = {$orderId}");
        $order = mysqli_fetch_assoc($query);

        if ($order != NULL) {
            if ($order["tongtien"] == $vnp_Amount) {
                // If column status is null because of alter table, map to 'PENDING'
                $currentStatus = $order["status"];
                if (empty($currentStatus)) {
                    $currentStatus = 'PENDING';
                }

                if ($currentStatus == 'PENDING') {
                    if ($inputData['vnp_ResponseCode'] == '00' || $inputData['vnp_TransactionStatus'] == '00') {
                        $Status = 'PAID'; 
                    } else {
                        $Status = 'FAILED'; 
                    }
                    
                    mysqli_query($con, "UPDATE oder SET status = '{$Status}', vnpay_tranId = '{$vnpTranId}' WHERE id = {$orderId}");
                    
                    $returnData['RspCode'] = '00';
                    $returnData['Message'] = 'Confirm Success';
                } else {
                    $returnData['RspCode'] = '02';
                    $returnData['Message'] = 'Order already confirmed';
                }
            } else {
                $returnData['RspCode'] = '04';
                $returnData['Message'] = 'invalid amount';
            }
        } else {
            $returnData['RspCode'] = '01';
            $returnData['Message'] = 'Order not found';
        }
    } else {
        $returnData['RspCode'] = '97';
        $returnData['Message'] = 'Invalid signature';
    }
} catch (Exception $e) {
    $returnData['RspCode'] = '99';
    $returnData['Message'] = 'Unknow error';
}

echo json_encode($returnData);
