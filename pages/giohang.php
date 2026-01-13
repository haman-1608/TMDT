<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$server = "localhost";
$user = "root";
$password = "";
$db = "goodoptic";
$conn = mysqli_connect($server, $user, $password, $db);
if (!$conn) { die("Kết nối thất bại: " . mysqli_connect_error()); }

if (isset($_POST['action']) && $_POST['action'] == 'check_coupon') {
    while (ob_get_level()) { ob_end_clean(); }
    header('Content-Type: application/json');

    $code = mysqli_real_escape_string($conn, trim($_POST['coupon_code']));
    $total = floatval($_POST['current_total']);
    $date_now = date("Y-m-d");

    $sql = "SELECT * FROM promotions WHERE promotion_code = '$code' AND expiry_date >= '$date_now' LIMIT 1";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();

        if ($row['times'] <= 0) {
            echo json_encode(['status' => 'error', 'msg' => 'Mã giảm giá đã hết lượt sử dụng']);
            exit();
        }

        $phantram = isset($row['discount_percentage']) ? floatval($row['discount_percentage']) : 0;
        $discount_amount = ($total * $phantram) / 100;

        $max_giam = isset($row['max_discount_value']) ? floatval($row['max_discount_value']) : 0;
        if ($max_giam > 0 && $discount_amount > $max_giam) {
            $discount_amount = $max_giam;
        }

        $discount_amount = min($discount_amount, $total);
        $new_total = $total - $discount_amount;

        echo json_encode([
            'status' => 'success',
            'discount' => $discount_amount,
            'new_total' => $new_total,
            'discount_format' => number_format($discount_amount, 0, ',', '.') . ' VNĐ',
            'msg' => 'Áp dụng mã thành công! (Giảm ' . $phantram . '%)'
        ]);
    } else {
        echo json_encode(['status' => 'error', 'msg' => 'Mã không tồn tại hoặc đã hết hạn']);
    }
    exit();
}

if (isset($_POST['update_quantity_id'])) {
    $id = $_POST['update_quantity_id'];
    $quantity = max(1, intval($_POST['update_quantity_value']));
    foreach ($_SESSION['cart'] as $index => $sp) {
        if ($sp['id'] == $id) {
            $_SESSION['cart'][$index]['quantity'] = $quantity;
            break;
        }
    }
    echo "<script>window.location.href='index.php?page=giohang';</script>";
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
    echo "<script>window.location.href='index.php?page=giohang';</script>";
    exit();
}

if (isset($_POST['thanhtoan'])) {
    $hoten = $_POST['hoten'] ?? '';
    $dt = $_POST['dt'] ?? '';
    $mail = $_POST['mail'] ?? '';
    $hinhthuc = $_POST['hinhthuc'] ?? '';
    
    $shipping_method = $_POST['shipping_method'] ?? 'home';
    
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
    $status = 'Đang xử lý';

    if (!isset($_COOKIE['customer_id'])) {
        $kh = mysqli_prepare($conn, "INSERT INTO customers (customer_name, email, phone, address) VALUES (?,?,?,?)");
        mysqli_stmt_bind_param($kh, 'ssss', $hoten, $mail, $dt, $fullAddress);
        mysqli_stmt_execute($kh);
        $customer_id = mysqli_insert_id($conn);
        setcookie("customer_id", $customer_id, time() + (86400 * 30));
    } else {
        $customer_id = intval($_COOKIE['customer_id']);
    }

    $applied_promotion_id = NULL; 

    $dh = mysqli_prepare($conn, "INSERT INTO orders (customer_id, customer_name, address, phone, email, pay_method, shipping_fee, total_discount, promotion_id, status) VALUES (?,?,?,?,?,?,?,?,?,?)");
    mysqli_stmt_bind_param($dh, 'isssssiiis', $customer_id, $hoten, $fullAddress, $dt, $mail, $hinhthuc, $phi_ship, $tien_giam, $applied_promotion_id, $status);
    mysqli_stmt_execute($dh);
    $order_id = mysqli_insert_id($conn);

    foreach ($_SESSION['cart'] as $sp) {
        $ctdh = mysqli_prepare($conn, "INSERT INTO order_details (order_id, product_id, price, quantity, total) VALUES (?,?,?,?,?)");
        $total_ct = $sp['price'] * $sp['quantity'];
        mysqli_stmt_bind_param($ctdh, 'iidid', $order_id, $sp['id'], $sp['price'], $sp['quantity'], $total_ct);
        mysqli_stmt_execute($ctdh);
    }

    try {
        if(file_exists("./mail/sendmail.php")) {
            require_once "./mail/sendmail.php"; 
            guiMailThanhToan($mail, $hoten); 
        }
    } catch (Exception $e) {}

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
    <form class="ttvc" method="post" name="infor" id="infor">
        <b style="font-size: clamp(19px, 2.5vw, 25px)">THÔNG TIN NGƯỜI NHẬN</b> <br>
        <i style="font-size: clamp(10px, 2.5vw, 13px);">Vui lòng nhập đầy đủ các thông tin bên dưới</i>

        <div>
            <p>Họ và tên *</p>
            <input type="text" name="hoten" placeholder="Họ và tên của bạn" required>
        </div>
        <div style="display: flex; gap: 9%">
            <div class="sdt" style="width: 40%;">
                <p>Số điện thoại *</p>
                <input type="text" name="dt" placeholder="Số điện thoại" required>
            </div>
            <div class="email" style="width: 45%">
                <p>Email</p>
                <input type="text" name="mail" placeholder="Email của bạn">
            </div>
        </div>

        <div style="margin-top: 15px; margin-bottom: 10px;">
            <b style="font-size: 16px;">Hình thức nhận hàng:</b>
            <div style="display: flex; gap: 20px; margin-top: 5px;">
                <label style="cursor: pointer;">
                    <input type="radio" name="shipping_method" value="home" checked onchange="toggleShipping('home')"> Giao tận nơi
                </label>
                <label style="cursor: pointer;">
                    <input type="radio" name="shipping_method" value="store" onchange="toggleShipping('store')"> Lấy tại cửa hàng
                </label>
            </div>
        </div>

        <div id="delivery-address" class="noio" style="width: 96%;">
            <div>
                <p>Tỉnh/Thành phố *</p>
                <select name="tinh" id="tinh" style="width: 90%;" required>
                    <option value="">Chọn Tỉnh/Thành</option>
                </select>
            </div>
            <div>
                <p>Quận/Huyện *</p>
                <select name="huyen" id="huyen" style="width: 90%;" required>
                    <option value="">Chọn Quận/Huyện</option>
                </select>
            </div>
            <div>
                <p>Xã/Phường *</p>
                <select name="xa" id="xa" style="width: 90%;" required>
                    <option value="">Chọn Xã/Phường</option>
                </select>
            </div>
            <div style="width: 100%;">
                <p>Số nhà *</p>
                <input type="text" name="sonha" id="sonha" placeholder="Ví dụ: Số 20, Võ Oanh..." style="width: 90%;" required>
            </div>
        </div>

        <div id="store-address" style="display: none; width: 96%; margin-bottom: 15px;">
            <p>Chọn cơ sở gần bạn *</p>
            <select name="store_branch" id="store_branch" style="width: 100%; padding: 10px;">
                <option value="CS1: Số 2, Đường Võ Oanh, Phường 25, Quận Bình Thạnh, TP.HCM">CS1: Số 2, Đường Võ Oanh, P.25, Q.Bình Thạnh, TP.HCM</option>
                <option value="CS2: Số 70 Đường Tô Ký, Phường Tân Chánh Hiệp, Quận 12, TP.HCM">CS2: Số 70 Đường Tô Ký, P.Tân Chánh Hiệp, Q.12, TP.HCM</option>
                <option value="CS3: Số 10 Đường số 12, KP3, P. An Khánh, TP.Thủ Đức">CS3: Số 10 Đường số 12, KP3, P. An Khánh, TP.Thủ Đức</option>
            </select>
        </div>

        <div>
            <p>Ghi chú</p>
            <textarea name="note" sps="6" placeholder="Ghi chú đơn hàng..."></textarea>
        </div>

        <div class="vanchuyen" style="margin-top: 20px;">
             <b style="font-size: clamp(19px, 2.5vw, 25px);">VẬN CHUYỂN</b>
             <div style="margin-top:10px; border:1px solid #ddd; padding:15px; border-radius:4px; display:flex; justify-content:space-between; align-items:center;">
                <span>Phí vận chuyển:</span>
                <span id="display_ship_fee" style="font-weight:bold;">0 VNĐ</span>
                <input type="hidden" name="shipping_fee_value" id="shipping_fee_value" value="0">
             </div>
        </div>

        <div class="htthanhtoan" style="margin-top: 20px;">
            <b style="font-size: clamp(19px, 2.5vw, 25px);">HÌNH THỨC THANH TOÁN</b>
            <label>Thanh toán khi nhận hàng<input type="radio" name="hinhthuc" value="Tiền mặt" checked="true"></label>
            <label>Chuyển khoản ngân hàng<input type="radio" name="hinhthuc" value="Chuyển khoản"></label>
            <p style="margin: -10px 3px; font-size: clamp(10px, 2vw, 13px);">Thông tin cá nhân...</p>
            
            <input type="hidden" name="discount_value_final" id="discount_value_final" value="0">
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
                            if (preg_match('#^https?://#i', $imgInput)) { $imgSrc = $imgInput; } 
                            else { 
                                $localPath = 'imgs/products/' . $imgInput;
                                $imgSrc = file_exists($localPath) ? $localPath : 'imgs/products/default.jpg';
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
            <?php $total += $sp['price'] * $sp['quantity']; ?>
        <?php endforeach; ?>
        
        <b style="font-size: clamp(19px, 2.5vw, 25px);">MÃ GIẢM GIÁ</b>
        <div class="giamgia" style="display: flex; gap:10px; margin-top:20px; margin-bottom: 20px;">
            <input style="width:60%; font-size: clamp(13px, 2vw, 17px);" type="text" name="magiamgia" id="magiamgia" placeholder="NHẬP MÃ GIẢM GIÁ">
            <button name="magiam" >ÁP DỤNG</button>
        </div>
        
        <div class="tien">
            <b>TẠM TÍNH</b>
            <p><?php echo number_format($total, 0, ',', '.') . ' VNĐ'; ?></p>
        </div>
        <div class="tien">
            <b>PHÍ VẬN CHUYỂN</b>
            <p id="display_ship_cart">0 VNĐ</p>
        </div>
        <div class="tien">
            <b>TIỀN GIẢM</b>
            <p id="display_discount">0 VNĐ</p>
        </div>
        <hr style="border-top:1px solid #ddd; margin:15px 0;">
        <div class="tien">
            <b>TỔNG TIỀN</b>
            <p id="display_total" style="color: #d70018; font-size: 1.2em;"><?php echo number_format($total, 0, ',', '.') . ' VNĐ'; ?></p>
        </div>
        
        <?php if($total >= 3000000): ?>
            <p style="text-align: right; color: green; font-style: italic;">* Đơn hàng trên 3.000.000đ được miễn phí vận chuyển!</p>
        <?php endif; ?>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
var baseTotal = <?php echo $total; ?>;
var currentDiscount = 0;
var currentShip = 0;
var shippingMethod = 'home';

function formatMoney(n) {
    return new Intl.NumberFormat('vi-VN').format(n) + ' VNĐ';
}

function updateTotal() {
    var finalTotal = baseTotal + currentShip - currentDiscount;
    if(finalTotal < 0) finalTotal = 0;
    $('#display_total').text(formatMoney(finalTotal));
}

window.toggleShipping = function(method) {
    shippingMethod = method;
    if (method === 'store') {
        $('#delivery-address').hide();
        $('#store-address').show();
        $('#tinh, #huyen, #xa, #sonha').prop('required', false);
        currentShip = 0;
        updateShippingUI();
    } else {
        $('#delivery-address').show();
        $('#store-address').hide();
        $('#tinh, #huyen, #xa, #sonha').prop('required', true);
        calculateShippingFee();
    }
}

function updateShippingUI() {
    $('#shipping_fee_value').val(currentShip);
    $('#display_ship_fee').text(formatMoney(currentShip));
    $('#display_ship_cart').text(formatMoney(currentShip));
    updateTotal();
}

function calculateShippingFee() {
    if (baseTotal >= 3000000) {
        currentShip = 0;
        updateShippingUI();
        return;
    }

    var tenTinh = $('#tinh').val();
    if (tenTinh === "") {
        currentShip = 0;
    } else if (mienBac.includes(tenTinh)) {
        currentShip = 40000;
    } else if (mienTrung.includes(tenTinh)) {
        currentShip = 30000;
    } else {
        currentShip = 20000; 
    }
    updateShippingUI();
}

const mienBac = ["Thành phố Hà Nội","Tỉnh Hà Giang","Tỉnh Cao Bằng","Tỉnh Bắc Kạn","Tỉnh Tuyên Quang","Tỉnh Lào Cai","Tỉnh Điện Biên","Tỉnh Lai Châu","Tỉnh Sơn La","Tỉnh Yên Bái","Tỉnh Hoà Bình","Tỉnh Thái Nguyên","Tỉnh Lạng Sơn","Tỉnh Quảng Ninh","Tỉnh Bắc Giang","Tỉnh Phú Thọ","Tỉnh Vĩnh Phúc","Tỉnh Bắc Ninh","Tỉnh Hải Dương","Thành phố Hải Phòng","Tỉnh Hưng Yên","Tỉnh Thái Bình","Tỉnh Hà Nam","Tỉnh Nam Định","Tỉnh Ninh Bình"];
const mienTrung = ["Tỉnh Thanh Hóa","Tỉnh Nghệ An","Tỉnh Hà Tĩnh","Tỉnh Quảng Bình","Tỉnh Quảng Trị","Tỉnh Thừa Thiên Huế","Thành phố Đà Nẵng","Tỉnh Quảng Nam","Tỉnh Quảng Ngãi","Tỉnh Bình Định","Tỉnh Phú Yên","Tỉnh Khánh Hòa","Tỉnh Ninh Thuận","Tỉnh Bình Thuận","Tỉnh Kon Tum","Tỉnh Gia Lai","Tỉnh Đắk Lắk","Tỉnh Đắk Nông","Tỉnh Lâm Đồng"];

$(document).ready(function() {
    $('button[name="magiam"]').click(function(e) {
        e.preventDefault(); 
        var code = $('#magiamgia').val();

        $.ajax({
            url: window.location.href,
            method: 'POST',
            data: {
                action: 'check_coupon',
                coupon_code: code,
                current_total: baseTotal
            },
            dataType: 'json',
            success: function(response) {
                if(response.status == 'success') {
                    currentDiscount = parseFloat(response.discount);
                    $('#discount_value_final').val(currentDiscount);
                    $('#display_discount').text(response.discount_format);
                    updateTotal();
                    alert(response.msg);
                } else {
                    alert(response.msg);
                }
            },
            error: function(xhr, status, error) {
                console.log(xhr.responseText);
                alert("Lỗi kết nối.");
            }
        });
    });

    $.getJSON('https://esgoo.net/api-tinhthanh/1/0.htm', function(data_tinh) {
        if (data_tinh.error == 0) {
            $.each(data_tinh.data, function(key_tinh, val_tinh) {
                $('#tinh').append(`<option value="${val_tinh.full_name}" data-id="${val_tinh.id}">${val_tinh.full_name}</option>`);
            });
        }

        $("#tinh").change(function() {
            if(shippingMethod === 'home') {
                calculateShippingFee();
            }

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
    
    calculateShippingFee();
});
</script>
