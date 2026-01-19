<?php
    if ($_GET['resultCode'] == '0') {

        $order_id = $_GET['orderId'];
        $trans_code = $_GET['transId'];

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

        unset($_SESSION['cart']);
    }
    echo
    '<div align="center" style="min-height: 450px; margin-top: 80px;">
        <img style="width: 200px; height: 150px;" src="imgs/thanks_icon.jpg" alt="thanks icon"/>
        <h3 style="color: gray;">Cảm ơn bạn đã mua hàng!</h3>
        <p style="color: gray;">Bạn đã thanh toán bằng MOMO thành công. Đơn hàng của bạn đang được xử lý.</p>
        <p style="color: gray;">Chúc bạn một ngày tốt lành!</p>
        <button name="back_to_home" style="cursor: pointer; background-color: black; color: white; border-radius: 30px; font-weight: bold; font-size: clamp(12px, 2vw, 20px); padding: 10px 20px;">
            Quay về trang chủ
        </button>
    </div>';
?>

<script>
$(document).ready(function() {
    // Lắng nghe sự kiện click vào nút Áp dụng
    $('button[name="back_to_home"]').click(function(e) {
        e.preventDefault(); // Ngăn chặn hành vi mặc định của nút
        window.location.href = 'index.php'; // Chuyển hướng về trang chủ
    });
});
</script>
<script type="text/javascript">
    $(document).ready(function() {
        $('button[name="back_to_home"]').hover(
            function() {
                $(this).css({
                    "background-color": "white",
                    "color": "black"
                });
            },
            function() {
                $(this).css({
                    "background-color": "black",
                    "color": "white"
                });
            }
        );
    })
</script>