<?php
// 1. KHỞI TẠO VÀ KẾT NỐI DATABASE
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$server = "localhost";
$user = "root";
$password = "";
$db = "goodoptic";

$conn = mysqli_connect($server, $user, $password, $db);

if (!$conn) {
    die("Kết nối thất bại: " . mysqli_connect_error());
}

// Bật báo lỗi để debug (Tắt khi deploy thực tế)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Khởi tạo các biến mặc định để tránh lỗi Undefined Variable trong HTML
$order = null;
$result_items = null;
$error_message = "";
$status_text_color = "text-gray-600"; // Màu mặc định
$display_date = "";
$tam_tinh = 0;
$phi_ship = 30000;

// ---------------------------------------------------------
// 2. XỬ LÝ YÊU CẦU HỦY ĐƠN HÀNG (POST)
// (Đặt lên đầu để xử lý xong mới load lại trang)
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'cancel_order') {
    
    $cancel_id = $_POST['order_id'];

    // Kiểm tra trạng thái hiện tại
    $check_sql = "SELECT status FROM `orders` WHERE `order_id` = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $cancel_id);
    $check_stmt->execute();
    $result_check = $check_stmt->get_result();
    $order_check = $result_check->fetch_assoc();

    if ($order_check) {
        if ($order_check['status'] == 'Đã giao hàng') {
            echo "<script>alert('Lỗi: Không thể hủy đơn hàng đã giao thành công!');</script>";
        } 
        elseif ($order_check['status'] == 'Đã hủy') {
             echo "<script>alert('Đơn hàng này đã bị hủy trước đó rồi.');</script>";
        }
        else {
            // Hợp lệ -> Update
            $update_sql = "UPDATE `orders` SET `status` = 'Đã hủy' WHERE `order_id` = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("i", $cancel_id);
            
            if ($update_stmt->execute()) {
                // Reload lại trang giữ nguyên các tham số GET để người dùng thấy kết quả ngay
                echo "<script>alert('Đã hủy đơn hàng thành công!'); window.location.href = window.location.href;</script>";
                exit; 
            } else {
                echo "<script>alert('Lỗi hệ thống, vui lòng thử lại sau.');</script>";
            }
        }
    }
}

// ---------------------------------------------------------
// 3. XỬ LÝ TRA CỨU ĐƠN HÀNG (GET)
// ---------------------------------------------------------
if (isset($_GET['order_code']) && isset($_GET['contact_info'])) { 
    
    // Lấy dữ liệu đúng tên biến
    $input_code = $_GET['order_code'];
    $input_contact = $_GET['contact_info'];

    // Lọc lấy số từ mã đơn
    $clean_id = preg_replace('/[^0-9]/', '', $input_code);

    if (!empty($clean_id)) {
        // ... (Đoạn SQL của bạn giữ nguyên) ...
        $sql = "SELECT * FROM `orders` 
                WHERE `order_id` = ? 
                AND (`phone` = ? OR `email` = ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iss", $clean_id, $input_contact, $input_contact);
        $stmt->execute();
        $result = $stmt->get_result();
        $order = $result->fetch_assoc();

        if ($order) {
            // --- NẾU TÌM THẤY ĐƠN HÀNG THÌ MỚI LÀM TIẾP ---
            
            $order_id = $order['order_id']; // Lấy ID chuẩn từ DB

            // A. Xử lý hiển thị ngày tháng
            $display_date = date("d/m/Y H:i", strtotime($order['created_at']));
            $order_code_display = "#DH-2024-" . $order_id;
            $status = $order['status'];

            // B. Xử lý màu sắc trạng thái
            $status_text_color = "text-yellow-600"; // Mặc định: Đang xử lý
            if ($order['status'] == 'Đã giao hàng') {
                $status_text_color = "text-green-600";
            } elseif ($order['status'] == 'Đã hủy') {
                $status_text_color = "text-red-600";
            }

            // C. Lấy danh sách sản phẩm (Chỉ chạy khi có $order)
            $sql_items = "SELECT od.*, p.product_name, p.images 
                          FROM `order_details` od
                          JOIN `products` p ON od.product_id = p.product_id
                          WHERE od.order_id = ?";
            
            $stmt_items = $conn->prepare($sql_items);
            $stmt_items->bind_param("i", $order_id);
            $stmt_items->execute();
            $result_items = $stmt_items->get_result();

        } else {
            $error_message = "Không tìm thấy đơn hàng! Vui lòng kiểm tra lại Mã đơn và SĐT/Email.";
        }
    } else {
        $error_message = "Mã đơn hàng không hợp lệ.";
    }
} else {
    $error_message = "Vui lòng nhập thông tin để tra cứu.";
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đơn Hàng của bạn</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>

</head>
<body class="bg-gray-50 text-gray-800">
    
    <div class="min-h-screen py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto space-y-6">
           
            <!-- Header Đơn Hàng -->
            <!-- ngày tạo đơn -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <div class="flex items-center gap-3 mb-1">
                        <h1 class="text-2xl font-bold text-gray-900">
                            Đơn hàng #DH-<?php echo htmlspecialchars($order_id); ?>
                        </h1>
                        
                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-800 border border-blue-200">
                            <?php echo htmlspecialchars($status); ?>
                        </span>
                    </div>

                    <p class="text-sm text-gray-500">
                        Đặt ngày: <?php echo $display_date; ?>
                    </p>
                </div>
            <!-- /ngày tạo đơn -->
            <!-- nút hủy đơn -->
                <?php if ($order['status'] != 'Đã giao hàng' && $order['status'] != 'Đã hủy'): ?>

                    <div class="flex-shrink-0">
                        <form method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn hủy đơn hàng này không? Hành động này không thể hoàn tác.');">
                            <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                            <input type="hidden" name="action" value="cancel_order">
                            
                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-red-300 shadow-sm text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors">
                                <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                Hủy đơn hàng
                            </button>
                        </form>
                    </div>

                <?php elseif ($order['status'] == 'Đã hủy'): ?>
                    <div class="flex-shrink-0">
                        <span class="px-4 py-2 text-sm font-medium text-red-500 bg-red-50 rounded-md border border-red-200">
                            Đơn hàng đã hủy
                        </span>
                    </div>

                <?php endif; ?>
            <!-- /nút hủy đơn -->
            </div>
            <!-- /Header Đơn Hàng -->


            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Cột trái: Thông tin -->
                <div class="space-y-6">
                    <!-- Khách hàng -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h2 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4 flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            Thông tin khách hàng
                        </h2>
                        
                        <div class="space-y-3">
                            <div class="font-medium text-gray-900">
                                <?php echo htmlspecialchars($order['customer_name']); ?>
                            </div>

                            <div class="text-sm text-gray-600 flex items-start">
                                <span class="block">
                                    <?php echo htmlspecialchars($order['email']); ?>
                                </span>
                            </div>

                            <div class="text-sm text-gray-600">
                                <?php echo htmlspecialchars($order['phone']); ?>
                            </div>

                            <div class="text-sm text-gray-600 border-t border-gray-100 pt-3 mt-2">
                                <?php echo htmlspecialchars($order['address']); ?>
                            </div>
                        </div>
                    </div>
                    <!-- /Khách hàng -->

                    <!-- Thanh toán -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h2 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4 flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                            Phương thức thanh toán
                        </h2>
                        
                        <div class="space-y-3">
                            <div class="text-sm text-gray-900 font-medium">
                                <?php echo htmlspecialchars($order['pay_method']); ?>
                            </div>

                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-500">Trạng thái:</span>
                                
                                <span class="font-medium <?php echo $status_text_color; ?>">
                                    <?php echo htmlspecialchars($order['status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <!-- /Thanh toán -->
                </div>
                <!-- /Cột trái: Thông tin -->

                <!-- Cột phải: Sản phẩm -->
                <div class="md:col-span-2 space-y-6">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                            <h2 class="text-xs font-bold text-gray-500 uppercase tracking-wider">Danh sách sản phẩm</h2>
                        </div>

                        <!-- Sản phẩm trong đơn hàng -->
                        <ul class="divide-y divide-gray-200 flex-grow">
                            <?php 
                            $cart_subtotal = 0;
                            while ($item = $result_items->fetch_assoc()): ?>
                                
                                <li class="p-6 hover:bg-gray-50 transition duration-150">
                                    <div class="flex items-start">
                                        
                                        <div class="flex-shrink-0 h-16 w-16 border border-gray-200 rounded-md overflow-hidden bg-white">
                                            <img 
                                                src="<?php echo htmlspecialchars($item['images']); ?>" 
                                                alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                                                class="h-full w-full object-cover"
                                            >
                                        </div>

                                        <div class="ml-4 flex-1">
                                            <div class="flex flex-col sm:flex-row sm:justify-between">
                                                <h3 class="text-sm font-medium text-gray-900 line-clamp-2">
                                                    <?php echo htmlspecialchars($item['product_name']); ?>
                                                </h3>
                                                
                                                <p class="text-sm font-medium text-gray-900 sm:ml-4 whitespace-nowrap mt-1 sm:mt-0">
                                                    <?php 
                                                        $total_line = $item['quantity'] * $item['price'];
                                                        echo number_format($total_line, 0, ',', '.');
                                                        $total_line = $item['quantity'] * $item['price'];
                                                        $cart_subtotal += $total_line;
                                                    ?> ₫
                                                </p>
                                            </div>
                                            
                                            <p class="mt-1 text-sm text-gray-500">
                                                Số lượng: <?php echo $item['quantity']; ?> x <?php echo number_format($item['price'], 0, ',', '.'); ?> ₫
                                            </p>
                                        </div>
                                    </div>
                                </li>

                            <?php endwhile; ?>
                        </ul>
                        <!-- /Sản phẩm trong đơn hàng -->

                        <!-- tổng tiền -->
                        <div class="bg-gray-50 px-6 py-6 border-t border-gray-200">

                            <!-- tạm tính --> 
                            <div class="flex justify-between text-sm mb-2 text-gray-600">
                                <span>Tạm tính</span>
                                <span>
                                    <?php echo number_format($cart_subtotal, 0, ',', '.'); ?> ₫
                                </span>
                            </div>
                            <!-- /tạm tính -->

                            <div class="flex justify-between text-sm mb-4 text-gray-600">
                                <span>Phí vận chuyển</span>
                                <span>30.000 ₫</span>
                            </div>
                            <div class="flex justify-between text-base font-bold text-gray-900 pt-4 border-t border-gray-200">
                                <span>Tổng cộng</span>
                                <span class="text-indigo-600 text-xl">7.570.000 ₫</span>
                            </div>
                        </div>
                        <!-- /tổng tiền -->
                    </div>
                </div>
            </div>
            <!-- /Cột phải: Sản phẩm -->

        </div>
    </div>
</body>
</html>