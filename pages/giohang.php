<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$server = "localhost";
$user = "root";
$password = "";
$db = "goodoptic";

$conn = mysqli_connect($server, $user, $password, $db);

if (!$conn) {
    die("Kết nối thất bại:" . mysqli_connect_error());
}

if (isset($_POST['action']) && $_POST['action'] == 'check_coupon') {
    $code = mysqli_real_escape_string($conn, trim($_POST['coupon_code']));
    $total = floatval($_POST['current_total']);
    $date_now = date("Y-m-d");

    // Truy vấn thêm các cột: usage_limit (giới hạn), times_used (đã dùng), discount_type (loại mã)
    $sql = "SELECT * FROM promotions WHERE promotion_code = '$code' AND expiry_date >= '$date_now' LIMIT 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        // 1. Kiểm tra số lần sử dụng (Giống hàm mẫu bạn đưa)
        if ($row['times_used'] >= $row['usage_limit']) {
            echo json_encode(['status' => 'error', 'msg' => 'Mã giảm giá đã hết lượt sử dụng']);
            exit();
        }

        // 2. Tính toán tiền giảm
        $discount_amount = 0;
        if ($row['discount_type'] == 'percent') {
            $discount_amount = ($total * $row['discount_percentage']) / 100;
            // Áp dụng giá giảm tối đa nếu có
            if ($row['max_discount_value'] > 0 && $discount_amount > $row['max_discount_value']) {
                $discount_amount = $row['max_discount_value'];
            }
        } else {
            // Loại giảm tiền cố định (fixed)
            $discount_amount = $row['discount_fixed_value'];
        }

        // 3. Không cho giảm quá tổng tiền
        $discount_amount = min($discount_amount, $total);
        $new_total = $total - $discount_amount;

        echo json_encode([
            'status' => 'success',
            'discount' => $discount_amount,
            'new_total' => $new_total,
            'discount_format' => number_format($discount_amount, 0, ',', '.') . ' VNĐ',
            'new_total_format' => number_format($new_total, 0, ',', '.') . ' VNĐ',
            'msg' => 'Áp dụng mã thành công!'
        ]);
    } else {
        echo json_encode(['status' => 'error', 'msg' => 'Mã không tồn tại hoặc đã hết hạn']);
    }
    exit();
}

if (isset($_POST['update_quantity_id']) && isset($_POST['update_quantity_value'])) {
    $id = $_POST['update_quantity_id'];
    $quantity = max(1, intval($_POST['update_quantity_value']));

    foreach ($_SESSION['cart'] as $index => $sp) {
        if ($sp['id'] == $id) {
            $_SESSION['cart'][$index]['quantity'] = $quantity;
            break;
        }
    }
    header("Location: index.php?page=giohang");
    exit();
}

if (isset($_POST['del_id'])) {
    $id = $_POST['del_id'];
    foreach ($_SESSION['cart'] as $index => $sp) {
        if ($sp['id'] == $id) {
            unset($_SESSION['cart'][$index]);
            $_SESSION['cart'] = array_values($_SESSION['cart']);
            break;
        }
    }
    header("Location: index.php?page=giohang");
    exit();
}

if (isset($_POST['thanhtoan'])) {
    $hoten = $_POST['hoten'] ?? '';
    $dt = $_POST['dt'] ?? '';
    $mail = $_POST['mail'] ?? '';
    $tinh = $_POST['tinh'] ?? '';
    $xa = $_POST['xa'] ?? '';
    $sonha = $_POST['sonha'] ?? '';
    $hinhthuc = $_POST['hinhthuc'] ?? '';
    $status = 'Đang xử lý';

    $fullAddress = "$sonha, $xa, $tinh";

    if (!isset($_COOKIE['customer_id'])) {
        $kh = mysqli_prepare($conn, "INSERT INTO customers (customer_name, email, phone, address) VALUES (?,?,?,?)");
        mysqli_stmt_bind_param($kh, 'ssss', $hoten, $mail, $dt, $fullAddress);
        mysqli_stmt_execute($kh);
        $customer_id = mysqli_insert_id($conn);
        setcookie("customer_id", $customer_id, time() + (86400 * 30));
    } else {
        $customer_id = intval($_COOKIE['customer_id']);
    }

    $applied_promotion_id = !empty($_POST['applied_promotion_id']) ? intval($_POST['applied_promotion_id']) : NULL;

    $dh = mysqli_prepare($conn, "INSERT INTO orders 
    (customer_id, customer_name, address, phone, email, pay_method, promotion_id, status) 
    VALUES (?,?,?,?,?,?,?,?)");
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

    foreach ($_SESSION['cart'] as $sp) {
        $product_id = $sp['id'];
        $quantity = $sp['quantity'];
        $price = $sp['price'];
        $total_ct = $price * $quantity;

        $ctdh = mysqli_prepare($conn, "INSERT INTO order_details (order_id, product_id, price, quantity, total) VALUES (?,?,?,?,?)");
        mysqli_stmt_bind_param($ctdh, 'iidid', $order_id, $product_id, $price, $quantity, $total_ct);
        mysqli_stmt_execute($ctdh);
    }

    try {
        require_once "./mail/sendmail.php"; 

        // 2. Gọi hàm gửi mail
        // $mail là email khách nhập từ form, $hoten là tên khách nhập từ form
        guiMailThanhToan($mail, $hoten); 

        } catch (Exception $e) {
        // Nếu lỗi gửi mail thì vẫn chạy tiếp để khách không thấy trang trắng
    }
    

    unset($_SESSION['cart']);
    echo "<script>alert('Đặt hàng thành công!'); window.location='index.php';</script>";
    exit();
}

if (empty($_SESSION['cart'])) {
    echo '<div align="center" style="min-height: 450px; margin-top: 80px;">
            <img style="width: 130px; height: 130px;" src="imgs/solar--cart-3-broken.svg" alt="">
            <h3 style="color: gray;">CHƯA CÓ SẢN PHẨM NÀO TRONG GIỎ</h3>
          </div>';
    return;
}
?>


<div class="giohang">
    <form class="ttvc" method="post" name="infor" id="infor" onsubmit="return checkInfomation();">
        <b style="font-size: clamp(19px, 2.5vw, 25px)">THÔNG TIN VẬN CHUYỂN</b> <br>
        <i style="font-size: clamp(10px, 2.5vw, 13px);">Vui lòng nhập đầy đủ các thông tin bên dưới</i>

        <div>
            <p>Họ và tên *</p>
            <input type="text" name="hoten" placeholder="Họ và tên của bạn">
        </div>
        <div style="display: flex; gap: 9%">
            <div class="sdt" style="width: 40%;">
                <p>Số điện thoại *</p>
                <input type="text" name="dt" placeholder="Số điện thoại của bạn">
            </div>
            <div class="email" style="width: 45%">
                <p>Email</p>
                <input type="text" name="mail" placeholder="Email của bạn">
            </div>
        </div>
        <div class="noio" style="width: 96%;">
            <div>
                <p>Tỉnh/Thành phố *</p>
                <select name="tinh" id="tinh" style="width: 90%;">
                    <option value="">Chọn Tỉnh/Thành</option>

                </select>
            </div>
            <div>
                <p>Quận/Huyện *</p>
                <select name="huyen" id="huyen" style="width: 90%;">
                    <option value="">Chọn Quận/Huyện</option>
                </select>
            </div>
            <div>
                <p>Xã/Phường *</p>
                <select name="xa" id="xa" style="width: 90%;">
                    <option value="">Chọn Xã/Phường</option>
                </select>
            </div>
        </div>
        <div>
            <p>Số nhà *</p>
            <input type="text" name="sonha" placeholder="Ví dụ: Số 20, Võ Oanh...">
        </div>
        <div>
            <p>Chú thích</p>
            <textarea name="note" sps="6" placeholder="Chú thích cho đơn hàng của bạn về đơn hàng hoặc về vận chuyển,..."></textarea>
        </div>
        <div class="htthanhtoan" style="margin-top: 20px;">
            <b style="font-size: clamp(19px, 2.5vw, 25px);">HÌNH THỨC THANH TOÁN</b>
            <label>Thanh toán khi nhận hàng<input type="radio" name="hinhthuc" value="Tiền mặt" checked="true"></label>
            <label>Chuyển khoản ngân hàng<input type="radio" name="hinhthuc" value="Chuyển khoản"></label>
            <p style="margin: -10px 3px; font-size: clamp(10px, 2vw, 13px);">Thông tin cá nhân của bạn được sử dụng để xử lý đơn hàng, trải nghiệm trên trang web và các mục đích khác được mô tả trong <b>chính sách bảo mật</b> của chúng tôi.</p>
            <input type="submit" name="thanhtoan" id="thanhtoan" value="THANH TOÁN"></input>
        </div>
    </form>

    <div class="gh">
        <b style="font-size: clamp(19px, 2.5vw, 25px);">GIỎ HÀNG</b>
        <?php $total = 0; ?>
        <?php foreach ($_SESSION['cart'] as $sp): ?>
            <div class="sp_cart" style="margin: 25px 0;">
                <a href="index.php?page=chitiet&id=<?php echo $sp['id']; ?>" class="sp">
                    <div class="ndsp" style="display: flex; flex-direction: row; gap: 30px; justify-content: flex-start;">
                        <div class="anhsp">
                            <?php
                            $imgInput = $sp['imgs'];

                            // Nếu là URL tuyệt đối
                            if (preg_match('#^https?://#i', $imgInput)) {
                                $imgSrc = $imgInput;
                            } else {
                                // Là tên file ảnh, nối vào thư mục local
                                $localPath = 'imgs/products/' . $imgInput;

                                // Kiểm tra file có tồn tại không
                                if (file_exists($localPath)) {
                                    $imgSrc = $localPath;
                                } else {
                                    $imgSrc = 'imgs/products/default.jpg'; // fallback ảnh mặc định
                                }
                            }
                            ?>
                            <img src="<?php echo $imgSrc; ?>" alt="Ảnh sản phẩm" loading="lazy">
                        </div>
                        <div style="width: 100%;">
                            <p class="tensp" style="margin: 0; font-weight: 500;"> <a href="index.php?page=chitiet&id=<?php echo $sp['id']; ?>" style="text-decoration: underline; color: black;"><?php echo $sp['name']; ?></a></p>
                            <p class="gia" style="margin-top: 20px; font-size: clamp(13px, 2.5vw, 20px)"><?php echo number_format($sp['price'], 0, ',', '.') . ' đ'; ?></p>
                            <form method="post" style="display: inline-block;">
                                <input type="hidden" name="update_quantity_id" value="<?php echo $sp['id']; ?>">
                                <input type="number" name="update_quantity_value" value="<?php echo $sp['quantity']; ?>" min="1" onchange="this.form.submit()">
                            </form>
                        </div>
                        <form method="post">
                            <input type="hidden" name="del_id" value="<?php echo $sp['id']; ?>">
                            <input type="submit" name="del" value="X">
                        </form>
                    </div>
                </a>
            </div>
            <?php
            $t = $sp['price'] * $sp['quantity'];
            $total += $t; ?>
        <?php endforeach; ?>
        <b style="font-size: clamp(19px, 2.5vw, 25px);">MÃ GIẢM GIÁ</b>
        <div class="giamgia" style="display: flex; gap:10px; margin-top:20px; margin-bottom: 20px;">
            <input style="width:60%; font-size: clamp(13px, 2vw, 17px);" type="text" name="magiamgia" id="magiamgia" placeholder="NHẬP MÃ GIẢM GIÁ">
            <button name="magiam" >ÁP DỤNG</button>
        </div>
        <div class="tien">
            <b>TIỀN GIẢM</b>
            <p id="display_discount">0 VNĐ</p>
        </div>
        <div class="tien">
            <b>TỔNG TIỀN</b>
            <p id="display_total"><?php echo number_format($total, 0, ',', '.') . ' VNĐ'; ?></p>
        </div>
</div>


<?php

?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // Lắng nghe sự kiện click vào nút Áp dụng
    $('button[name="magiam"]').click(function(e) {
        e.preventDefault(); // Ngăn form load lại trang

        var code = $('#magiamgia').val();
        var currentTotal = <?php echo $total; ?>; // Lấy tổng tiền ban đầu từ PHP

        $.ajax({
            url: window.location.href, // Gửi yêu cầu đến chính trang này
            method: 'POST',
            data: {
                action: 'check_coupon',
                coupon_code: code,
                current_total: currentTotal
            },
            dataType: 'json',
            success: function(response) {
                if(response.status == 'success') {
                    // Cập nhật số tiền hiển thị trên giao diện
                    $('#display_discount').text(response.discount_format);
                    $('#display_total').text(response.new_total_format);
                    alert(response.msg);
                } else {
                    alert(response.msg);
                }
            }
        });
    });
});
</script>
<script>
    $(document).ready(function() {
        // 1. Lấy danh sách Tỉnh/Thành
        $.getJSON('https://esgoo.net/api-tinhthanh/1/0.htm', function(data_tinh) {
            if (data_tinh.error == 0) {
                $.each(data_tinh.data, function(key_tinh, val_tinh) {
                    // Lưu ID vào attribute để dùng lấy Huyện, lưu Name vào value để gửi form PHP
                    $('#tinh').append(`<option value="${val_tinh.full_name}" data-id="${val_tinh.id}">${val_tinh.full_name}</option>`);
                });
            }

            // 2. Khi chọn Tỉnh -> Lấy Huyện
            $("#tinh").change(function() {
                var id_tinh = $(this).find(':selected').data('id');
                $('#huyen').html('<option value="">Chọn Quận/Huyện</option>');
                $('#xa').html('<option value="">Chọn Xã/Phường</option>');
                
                if (id_tinh) {
                    $.getJSON(`https://esgoo.net/api-tinhthanh/2/${id_tinh}.htm`, function(data_huyen) {
                        if (data_huyen.error == 0) {
                            $.each(data_huyen.data, function(key_huyen, val_huyen) {
                                $('#huyen').append(`<option value="${val_huyen.full_name}" data-id="${val_huyen.id}">${val_huyen.full_name}</option>`);
                            });
                        }
                    });
                }
            });

            // 3. Khi chọn Huyện -> Lấy Xã
            $("#huyen").change(function() {
                var id_huyen = $(this).find(':selected').data('id');
                $('#xa').html('<option value="">Chọn Xã/Phường</option>');
                
                if (id_huyen) {
                    $.getJSON(`https://esgoo.net/api-tinhthanh/3/${id_huyen}.htm`, function(data_xa) {
                        if (data_xa.error == 0) {
                            $.each(data_xa.data, function(key_xa, val_xa) {
                                $('#xa').append(`<option value="${val_xa.full_name}">${val_xa.full_name}</option>`);
                            });
                        }
                    });
                }
            });
        });
    });
</script>