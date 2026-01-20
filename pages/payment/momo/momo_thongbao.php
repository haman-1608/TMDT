<?php
    // Kiểm tra session trước khi unset
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Kết nối database (giả sử bạn đã include file cấu hình ở đâu đó, nếu chưa thì thêm vào)
    // include 'db_connection.php'; 

    if (isset($_GET['resultCode']) && $_GET['resultCode'] == '0') {
        
        // VÁ LỖI BẢO MẬT: Ép kiểu dữ liệu về số nguyên
        $order_id = intval($_GET['orderId']); 
        
        // Chống SQL Injection cho chuỗi string
        $trans_code = mysqli_real_escape_string($conn, $_GET['transId']);

        // Thực hiện Update
        mysqli_query($conn,
            "UPDATE payments
            SET payment_status='Đã thanh toán',
                transaction_code='$trans_code',
                payment_date=NOW()
            WHERE order_id=$order_id"
        );

        mysqli_query($conn,
            "UPDATE orders SET status='Đã xác nhận' WHERE order_id=$order_id"
        );

        // Xóa giỏ hàng
        if(isset($_SESSION['cart'])) {
            unset($_SESSION['cart']);
        }
    }
?>

<style>
    .btn-home {
        cursor: pointer;
        background-color: black;
        color: white;
        border-radius: 30px;
        font-weight: bold;
        font-size: clamp(12px, 2vw, 20px);
        padding: 10px 20px;
        border: 2px solid black; /* Thêm viền để khi đổi màu không bị giật */
        transition: all 0.3s ease; /* Hiệu ứng chuyển màu mượt mà */
    }

    /* Hiệu ứng khi di chuột vào */
    .btn-home:hover {
        background-color: white;
        color: black;
    }
</style>

<div align="center" style="min-height: 450px; margin-top: 80px;">
    <img style="width: 200px; height: 150px;" src="imgs/thanks_icon.jpg" alt="thanks icon"/>
    <h3 style="color: gray;">Cảm ơn bạn đã mua hàng!</h3>
    <p style="color: gray;">Bạn đã thanh toán bằng MOMO thành công. Đơn hàng của bạn đang được xử lý.</p>
    <p style="color: gray;">Chúc bạn một ngày tốt lành!</p>
    
    <a href="index.php" style="text-decoration: none;">
        <button class="btn-home" name="back_to_home">
            Quay về trang chủ
        </button>
    </a>
</div>