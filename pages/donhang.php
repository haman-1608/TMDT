<?php
    //kết nối db
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

    //biến báo lỗi
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    //kết nối bảng orders
    $sql = "SELECT * FROM `orders` WHERE `order_id` = ?";

    //ngày theo đơn hàng
    $order_id = 3; 
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result(); 
    $order = $result->fetch_assoc(); 

    if ($order) {
        $raw_date = $order['created_at']; 

        $display_date = date("d/m/Y H:i", strtotime($raw_date));

        $order_code = "#DH-2024-" . $order['order_id'];
        $status = $order['status'];
    } else {
        $display_date = "Không xác định";
        $order_code = "N/A";
        $status = "N/A";
    }

    //thanh toán
    // Mặc định là màu vàng (cho trạng thái Đang xử lý, Chờ duyệt...)
    $status_text_color = "text-yellow-600"; 

    // Nếu đã giao hàng thành công -> Màu xanh lá
    if ($order['status'] == 'Đã giao hàng') {
        $status_text_color = "text-green-600";
    } 
    // Nếu đã hủy -> Màu đỏ
    elseif ($order['status'] == 'Đã hủy') {
        $status_text_color = "text-red-600";
    }

    //kết nối bảng order_details và bảng products
    $sql_items = "SELECT od.*, p.product_name, p.images 
              FROM `order_details` od
              JOIN `products` p ON od.product_id = p.product_id
              WHERE od.order_id = ?";

    $stmt_items = $conn->prepare($sql_items);
    $stmt_items->bind_param("i", $order_id);
    $stmt_items->execute();
    $result_items = $stmt_items->get_result();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview: donhang.php</title>
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
                <button onclick="alert('Trong file PHP thực tế, nút này sẽ gửi form để cập nhật database.')" class="inline-flex items-center px-4 py-2 border border-red-300 shadow-sm text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors">
                    <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Hủy đơn hàng
                </button>
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
                        <?php while ($item = $result_items->fetch_assoc()): ?>
    
                            <div class="flex py-4 border-b border-gray-100 last:border-0">
                                
                                <div class="h-20 w-20 flex-shrink-0 overflow-hidden rounded-md border border-gray-200">
                                    <img src="<?php echo htmlspecialchars($item['images']); ?>" alt="" class="h-full w-full object-cover object-center">
                                </div>

                                <div class="ml-4 flex-1 flex flex-col justify-center">
                                    <div class="flex justify-between">
                                        <h3 class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($item['product_name']); ?>
                                        </h3>
                                        
                                        <p class="text-sm font-medium text-gray-900 ml-4">
                                            <?php 
                                                $total_line = $item['quantity'] * $item['price'];
                                                echo number_format($total_line, 0, ',', '.'); 
                                            ?> ₫
                                        </p>
                                    </div>
                                    
                                    <p class="mt-1 text-sm text-gray-500">
                                        Số lượng: <?php echo $item['quantity']; ?> x <?php echo number_format($item['price'], 0, ',', '.'); ?> ₫
                                    </p>
                                </div>
                            </div>

                        <?php endwhile; ?>
                        <!-- /Sản phẩm trong đơn hàng -->

                        <!-- tổng tiền -->
                        <div class="bg-gray-50 px-6 py-6 border-t border-gray-200">
                            <div class="flex justify-between text-sm mb-2 text-gray-600">
                                <span>Tạm tính</span>
                                <span>7.540.000 ₫</span>
                            </div>
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