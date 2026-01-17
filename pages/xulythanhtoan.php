<?php
    require_once 'vnp_config.php';

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
    $port = 3307;
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
    $tinh    = $_POST['tinh'] ?? '';
    $xa      = $_POST['xa'] ?? '';
    $sonha   = $_POST['sonha'] ?? '';
    $hinhthuc = $_POST['hinhthuc'] ?? '';
    $status  = 'Đang xử lý';

    $fullAddress = "$sonha, $xa, $tinh";

    // ===== THÊM KHÁCH HÀNG =====
    // Kiểm tra khách hàng đã tồn tại chưa qua email
    $kh = mysqli_prepare($conn, "SELECT customer_id FROM customers WHERE email=?");
    mysqli_stmt_bind_param($kh, 's', $mail);
    mysqli_stmt_execute($kh);
    $rs = mysqli_stmt_get_result($kh);

    // Nếu tồn tại thì lấy customer_id
    if ($row = mysqli_fetch_assoc($rs)) {
    $customer_id = $row['customer_id'];
    }else{
        // Nếu không tồn tại thì thêm mới
        $kh = mysqli_prepare(
            $conn,
            "INSERT INTO customers (customer_name, email, phone, address) VALUES (?,?,?,?)"
        );
        mysqli_stmt_bind_param($kh, 'ssss', $hoten, $mail, $dt, $fullAddress);
        mysqli_stmt_execute($kh);

        $customer_id = mysqli_insert_id($conn);
        setcookie("customer_id", $customer_id, time() + (86400 * 30), "/");
    }

    // ===== KHUYẾN MÃI =====
    $applied_promotion_id = !empty($_POST['applied_promotion_id'])
        ? intval($_POST['applied_promotion_id'])
        : NULL;

    // ===== THÊM ĐƠN HÀNG =====
    $dh = mysqli_prepare(
        $conn,
        "INSERT INTO orders
        (customer_id, customer_name, address, phone, email, pay_method, promotion_id, status) 
        VALUES (?,?,?,?,?,?,?,?)"
    );

    $status = 'Đang xử lý';
    mysqli_stmt_bind_param(
        $dh,
        'isssssis',
        $customer_id,
        $hoten,
        $fullAddress,
        $dt,
        $mail,
        $hinhthuc,
        $applied_promotion_id,
        $status
    );
    mysqli_stmt_execute($dh);

    $order_id = mysqli_insert_id($conn);

    // ===== THÊM CHI TIẾT ĐƠN HÀNG =====
    foreach ($_SESSION['cart'] as $sp) {
        $product_id = $sp['id'];
        $quantity   = $sp['quantity'];
        $price      = $sp['price'];
        $total_amount   = $price * $quantity;

        $ctdh = mysqli_prepare(
            $conn,
            "INSERT INTO order_details (order_id, product_id, price, quantity, total)
            VALUES (?,?,?,?,?)"
        );
        mysqli_stmt_bind_param($ctdh, 'iidid', $order_id, $product_id, $price, $quantity, $total_amount);
        mysqli_stmt_execute($ctdh);
    }

    // ===== Thêm chi tiết thanh toán =====
    $cttt = mysqli_prepare(
        $conn,
        "INSERT INTO payments(order_id,payment_method,payment_amount)
        VALUES(?,?,?)"
    );
    mysqli_stmt_bind_param($cttt, 'isd', $order_id, $hinhthuc, $total_amount);
    mysqli_stmt_execute($cttt);
    $payment_id = mysqli_insert_id($conn);
    // ===== GỬI MAIL =====
    // try {
    //     require_once "./mail/sendmail.php";
    //     guiMailThanhToan($mail, $hoten);
    // } catch (Exception $e) {
    //     // bỏ qua lỗi mail
    // }


    // ===== XỬ LÝ THEO HÌNH THỨC THANH TOÁN =====
    if ($hinhthuc == 'Tiền mặt' || $hinhthuc == 'Chuyển khoản') {
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
        $vnp_TxnRef = $order_id;
        $vnp_OrderInfo = 'thanh toan don hang:' . $order_id;
        $vnp_OrderType = 'billpayment';
        $vnp_Amount = $total_amount * 100;
        $vnp_Locale = 'vn';
        $vnp_BankCode = 'NCB';
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
        $returnData = array('code' => '00'
        , 'message' => 'success'
        , 'data' => $vnp_Url);
        if (isset($_POST['thanhtoan'])) {
            header('Location: ' . $vnp_Url);
            die();
        } else {
            echo json_encode($returnData);
        }
    }elseif ($hinhthuc == 'Momo') {
        // ===== THANH TOÁN MOMO =====
        
    }



























    // ===== DỌN GIỎ HÀNG =====
    // unset($_SESSION['cart']);

    // echo "<script>
    //     alert('Đặt hàng thành công!');
    //     window.location='../index.php';
    // </script>";
    // exit();
?>