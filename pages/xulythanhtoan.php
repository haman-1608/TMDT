<?php
    // xulythanhtoan.php
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $server = "localhost";
    $user = "root";
    $password = "";
    $db = "goodoptic";

    // --- Cấu hình riêng ---
    $port = 3306;
    $socket = "mysql";
    $conn = mysqli_connect($server, $user, $password, $db, $port, $socket);
    // ======================

    if (!$conn) {
        die("Kết nối thất bại:" . mysqli_connect_error());
    }

    // Chặn truy cập trực tiếp vào trang xử lý thanh toán
    if (!isset($_POST['thanhtoan'])) {
        header('Location: giohang.php');
        exit();
    }

    // ===== LẤY DỮ LIỆU =====
    $hoten   = $_POST['hoten'] ?? '';
    $dt      = $_POST['dt'] ?? '';
    $mail    = $_POST['mail'] ?? '';
    $hinhthuc = $_POST['hinhthuc'] ?? '';
    $momo_channel = $_POST['momo_channel'] ?? null;
    $shipping_method = $_POST['shipping_method'] ?? 'home';

    // Xử lý địa chỉ nhận hàng
    if ($shipping_method == 'store') {
        $branch = $_POST['store_branch'] ?? '';
        $fullAddress = "Nhận tại: " . $branch;
        $tinh = ""; $xa = ""; $sonha = ""; 
    } else {
        $tinh = $_POST['tinh'] ?? '';
        $xa = $_POST['xa'] ?? '';
        $sonha = $_POST['sonha'] ?? '';
        $fullAddress = "$sonha, $xa, $tinh";
    }

    $phi_ship = isset($_POST['shipping_fee_value']) ? intval($_POST['shipping_fee_value']) : 0;
    $tien_giam = isset($_POST['discount_value_final']) ? intval($_POST['discount_value_final']) : 0;

    $total_cart = 0;
    if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $sp) {
            $total_cart += $sp['price'] * $sp['quantity'];
        }
    }

    // Tính tongtien cuối cùng để lưu vào database
    $tong_thanh_toan = ($total_cart + $phi_ship) - $tien_giam;
    if ($tong_thanh_toan < 0) $tong_thanh_toan = 0;

    $fullAddress = "$sonha, $xa, $tinh";
    $status = 'Đang xử lý';

    // ===== THÊM KHÁCH HÀNG =====
    // Kiểm tra khách hàng đã tồn tại chưa qua email
    $kh = mysqli_prepare($conn,
    "SELECT customer_id FROM customers WHERE email = ? LIMIT 1");
    mysqli_stmt_bind_param($kh, 's', $mail);
    mysqli_stmt_execute($kh);
    $rs = mysqli_stmt_get_result($kh);

    if ($row = mysqli_fetch_assoc($rs)) {
    // Nếu tồn tại thì lấy customer_id
    $customer_id = $row['customer_id'];
    }else{
        // Nếu không tồn tại ==> thêm mới
        $kh = mysqli_prepare(
            $conn,
            "INSERT INTO customers (customer_name, email, phone, address)
            VALUES (?,?,?,?)"
        );
        mysqli_stmt_bind_param($kh, 'ssss', $hoten, $mail, $dt, $fullAddress);
        mysqli_stmt_execute($kh);
        $customer_id = mysqli_insert_id($conn);
    }
    // Lưu cookie (30 ngày – dùng cho lần mua sau)
    setcookie("customer_id", $customer_id, time() + (86400 * 30), "/");

    // ===== KHUYẾN MÃI =====
    $applied_promotion_id = NULL;

    // ===== THÊM ĐƠN HÀNG =====
    $dh = mysqli_prepare(
        $conn,
        "INSERT INTO orders
        (customer_id, customer_name, address, phone, email, pay_method, status, tongtien, shipping_fee, total_discount, promotion_id)
        VALUES (?,?,?,?,?,?,?,?,?,?,?)"
    );
    mysqli_stmt_bind_param($dh, 'issssssdddi', $customer_id, $hoten, $fullAddress, $dt, $mail, $hinhthuc, $status, $tong_thanh_toan, $phi_ship, $tien_giam, $applied_promotion_id);
    mysqli_stmt_execute($dh);
    $order_id = mysqli_insert_id($conn);

    // ===== THÊM CHI TIẾT ĐƠN HÀNG =====
    foreach ($_SESSION['cart'] as $sp) {
        $ctdh = mysqli_prepare($conn,
            "INSERT INTO order_details
            (order_id, product_id, price, quantity, total)
            VALUES (?,?,?,?,?)"
        );
        $total_ct = $sp['price'] * $sp['quantity'];
        mysqli_stmt_bind_param($ctdh, 'iidid', $order_id, $sp['id'], $sp['price'], $sp['quantity'], $total_ct);
        mysqli_stmt_execute($ctdh);
    }

    $orderData = [
            'order_id' => $order_id,
            'customer_name' => $hoten,
            'email' => $mail,
            'phone' => $dt,
            'address' => $fullAddress,
            'cart' => $_SESSION['cart'], // Gửi cả giỏ hàng qua
            'shipping_fee' => $phi_ship,
            'discount' => $tien_giam,
            'total_all' => $tong_thanh_toan
        ];


        require_once __DIR__ . '/../mail/sendmail.php';
        guiMailThanhToan($orderData);

// ===== XỬ LÝ THEO HÌNH THỨC THANH TOÁN =====
if ($hinhthuc == 'Tiền mặt') {
    mysqli_query($conn,
    "UPDATE orders SET status='Đã xác nhận' WHERE order_id=$order_id"
    );

    // ===== DỌN GIỎ HÀNG =====
    unset($_SESSION['cart']);

    echo "<script>
        alert('Đặt hàng thành công!');
        window.location='../index.php';
    </script>";
    exit();
}elseif ($hinhthuc == "VNPAY") {
    // ===== THANH TOÁN VNPAY =====
    require_once 'payment/vnpay/vnp_config.php';

    $vnp_TxnRef = $order_id;
    $vnp_OrderInfo = 'thanh toan don hang:' . $order_id;
    $vnp_OrderType = 'billpayment';
    $vnp_Amount = $tong_thanh_toan * 100;
    $vnp_Locale = 'vn';
    $vnp_BankCode = '';
    $vnp_IpAddr = $_SERVER['REMOTE_ADDR'];
    $vnp_ExpireDate = $expire;

    $inputData = array(
        "vnp_Version" => "2.1.0",
        "vnp_TmnCode" => $vnp_TmnCode,
        "vnp_Amount" => $vnp_Amount,
        "vnp_Command" => "pay",
        "vnp_CreateDate" => date('YmdHis'),
        "vnp_CurrCode" => "VND",
        "vnp_IpAddr" => $vnp_IpAddr,
        "vnp_Locale" => $vnp_Locale,
        "vnp_OrderInfo" => $vnp_OrderInfo,
        "vnp_OrderType" => $vnp_OrderType,
        "vnp_ReturnUrl" => $vnp_Returnurl,
        "vnp_TxnRef" => $vnp_TxnRef,
        "vnp_ExpireDate"=>$vnp_ExpireDate
    );

    if (isset($vnp_BankCode) && $vnp_BankCode != "") {
        $inputData['vnp_BankCode'] = $vnp_BankCode;
    }

    //var_dump($inputData);
    ksort($inputData);
    $query = "";
    $i = 0;
    $hashdata = "";
    foreach ($inputData as $key => $value) {
        if ($i == 1) {
            $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
        } else {
            $hashdata .= urlencode($key) . "=" . urlencode($value);
            $i = 1;
        }
        $query .= urlencode($key) . "=" . urlencode($value) . '&';
    }

    $vnp_Url = $vnp_Url . "?" . $query;
    if (isset($vnp_HashSecret)) {
        $vnpSecureHash =   hash_hmac('sha512', $hashdata, $vnp_HashSecret);
        $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
    }
    header('Location: ' . $vnp_Url);
    exit();
}elseif ($hinhthuc == 'Momo') {
    // ===== THANH TOÁN MOMO =====
    if ($momo_channel == 'qr-code') {
        // XỬ LÝ MOMO QR CODE
        $config = json_decode(
            file_get_contents(__DIR__ . '/payment/momo/momo_config.json'),
            true
        );
        require_once 'payment/momo/momo_helper.php';
        $endpoint = "https://test-payment.momo.vn/v2/gateway/api/create";

        $orderInfo = "Thanh toán qua MoMo bằng QR code";
        $orderId = $order_id . '_' . time();
        $redirectUrl = "http://localhost/TMDT/index.php?page=payment/momo/momo_thongbao";
        $ipnUrl = "https://webhook.site/b3088a6a-2d17-4f8d-a383-71389a6c600b";
        $extraData = "";

        $partnerCode = $config['partnerCode'];
        $accessKey = $config['accessKey'];
        $secretkey = $config['secretKey'];
        $amount = $tong_thanh_toan;

        $requestId = time() . "";
        $requestType = "captureWallet";

        //before sign HMAC SHA256 signature
        $rawHash = "accessKey=" . $accessKey . "&amount=" . $amount . "&extraData=" . $extraData . "&ipnUrl=" . $ipnUrl . "&orderId=" . $orderId . "&orderInfo=" . $orderInfo . "&partnerCode=" . $partnerCode . "&redirectUrl=" . $redirectUrl . "&requestId=" . $requestId . "&requestType=" . $requestType;
        $signature = hash_hmac("sha256", $rawHash, $secretkey);
        $data = array('partnerCode' => $partnerCode,
            'partnerName' => "Test",
            "storeId" => "MomoTestStore",
            'requestId' => $requestId,
            'amount' => $amount,
            'orderId' => $orderId,
            'orderInfo' => $orderInfo,
            'redirectUrl' => $redirectUrl,
            'ipnUrl' => $ipnUrl,
            'lang' => 'vi',
            'extraData' => $extraData,
            'requestType' => $requestType,
            'signature' => $signature);
        $result = execPostRequest($endpoint, json_encode($data));
        $jsonResult = json_decode($result, true);

        if (!isset($jsonResult['payUrl'])) {
            echo '<pre>';
            print_r($jsonResult);
            exit;
        }
        header('Location: ' . $jsonResult['payUrl']);
        exit();
    }elseif ($momo_channel == 'atm') {
        // XỬ LÝ MOMO THẺ ATM
        $config = json_decode(
            file_get_contents(__DIR__ . '/payment/momo/momo_config.json'),
            true
        );
        require_once 'payment/momo/momo_helper.php';
        $endpoint = "https://test-payment.momo.vn/v2/gateway/api/create";

        $orderInfo = "Thanh toán qua MoMo bằng thẻ ATM";
        $orderId = $order_id . '_' . time();
        $redirectUrl = "http://localhost/TMDT/index.php?page=payment/momo/momo_thongbao";
        $ipnUrl = "https://webhook.site/b3088a6a-2d17-4f8d-a383-71389a6c600b";
        $extraData = "";
        $partnerCode = $config['partnerCode'];
        $accessKey = $config['accessKey'];
        $secretkey = $config['secretKey'];
        $amount = $tong_thanh_toan;
        $requestId = time() . "";
        $requestType = "payWithATM";

        //before sign HMAC SHA256 signature
        $rawHash = "accessKey=" . $accessKey . "&amount=" . $amount . "&extraData=" . $extraData . "&ipnUrl=" . $ipnUrl . "&orderId=" . $orderId . "&orderInfo=" . $orderInfo . "&partnerCode=" . $partnerCode . "&redirectUrl=" . $redirectUrl . "&requestId=" . $requestId . "&requestType=" . $requestType;
        $signature = hash_hmac("sha256", $rawHash, $secretkey);
        $data = array('partnerCode' => $partnerCode,
            'partnerName' => "Test",
            "storeId" => "MomoTestStore",
            'requestId' => $requestId,
            'amount' => $amount,
            'orderId' => $orderId,
            'orderInfo' => $orderInfo,
            'redirectUrl' => $redirectUrl,
            'ipnUrl' => $ipnUrl,
            'lang' => 'vi',
            'extraData' => $extraData,
            'requestType' => $requestType,
            'signature' => $signature);
        $result = execPostRequest($endpoint, json_encode($data));
        $jsonResult = json_decode($result, true);

        if (!isset($jsonResult['payUrl'])) {
            echo '<pre>';
            print_r($jsonResult);
            exit;
        }
        header('Location: ' . $jsonResult['payUrl']);
        exit();
    }else {
        die('Vui lòng chọn QR hoặc ATM cho Momo');
    }
}
?>