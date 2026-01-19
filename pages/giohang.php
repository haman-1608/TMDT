<?php
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


// ===== KIỂM TRA GIỎ HÀNG =====
if (empty($_SESSION['cart'])) {
    echo '<div align="center" style="min-height: 450px; margin-top: 80px;">
            <img style="width: 130px; height: 130px;" src="imgs/solar--cart-3-broken.svg" alt="">
            <h3 style="color: gray;">CHƯA CÓ SẢN PHẨM NÀO TRONG GIỎ</h3>
          </div>';
    return;
}

// ===== XỬ LÝ GIỎ HÀNG =====
if (isset($_POST['update_quantity_id'])) {
    // CẬP NHẬT SỐ LƯỢNG SẢN PHẨM
    $id = $_POST['update_quantity_id'];
    $quantity = max(1, intval($_POST['update_quantity_value']));
    // Cập nhật số lượng trong giỏ hàng
    foreach ($_SESSION['cart'] as $index => $sp) { 
        if ($sp['id'] == $id) {
            $_SESSION['cart'][$index]['quantity'] = $quantity;
            break;
        }
    }
    echo "<script>window.location.href='index.php?page=giohang';</script>";
    exit();
}

// XÓA SẢN PHẨM KHỎI GIỎ HÀNG
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
?>


<div class="giohang">
    <form class="ttvc" action="./pages/xulythanhtoan.php" method="post" name="infor" id="infor" onsubmit="return checkInfomation();">
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
            <label>
                <div class="payment-content">
                    <div class="payment-logo">
                        <img src="imgs/cash.png" alt="COD Icon" loading="lazy">
                    </div>
                    <div class="payment-text">Thanh toán khi nhận hàng</div>
                    <input type="radio" class="checker" name="hinhthuc" value="Tiền mặt" checked="true">
                </div>

            </label>
            <label>
                <div class="payment-content">
                    <div class="payment-logo">
                        <img src="imgs/banking.png" alt="Bank Icon" loading="lazy">
                    </div>
                    <div class="payment-text">Chuyển khoản ngân hàng</div>
                    <input type="radio" class="checker" name="hinhthuc" value="Chuyển khoản">
                </div>
            </label>
            <label>
                <div class="payment-content">
                    <div class="payment-logo">
                        <img src="imgs/momo.png" alt="Momo Icon" loading="lazy">
                    </div>
                    <div class="payment-text">Momo</div>
                    <input type="radio" class="checker" name="hinhthuc" value="Momo" id="momoRadio">
                </div>
            </label>
                <div id="momo-extra" style="display:none; margin-top:10px;">
                    <button type="button" data-channel="qr-code">QR Code</button>
                    <button type="button" data-channel="atm">Thẻ ATM</button>
                </div>
                <input type="hidden" name="momo_channel" id="momo_channel">
            <label>
                <div class="payment-content">
                    <div class="payment-logo">
                        <img src="imgs/vnpay.png" alt="VNPAY Icon" loading="lazy">
                    </div>
                    <div class="payment-text">VNPAY</div>
                    <input type="radio" class="checker" name="hinhthuc" value="VNPAY">
                </div>
            </label>
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
<script>
// ===== XỬ LÝ HIỂN THỊ THÊM CHI TIẾT THANH TOÁN QR/ATM KHI CHỌN MOMO =====
document.addEventListener('DOMContentLoaded', function () {
    const momoRadio = document.getElementById('momoRadio');
    const momoExtra = document.getElementById('momo-extra');

    momoRadio.addEventListener('change', function () {
        if (this.checked) {
            momoExtra.style.display = 'block';
        }
    });

    // Ẩn khi chọn phương thức khác
    document.querySelectorAll('input[name="hinhthuc"]').forEach(radio => {
        radio.addEventListener('change', function () {
            if (!momoRadio.checked) {
                momoExtra.style.display = 'none';
            }
        });
    });
});
document.querySelectorAll('#momo-extra button').forEach(btn => {
    btn.addEventListener('click', function () {
        document.getElementById('momo_channel').value = this.dataset.channel;
        alert('Đã chọn Momo ' + this.dataset.channel.toUpperCase());
    });
});
</script>