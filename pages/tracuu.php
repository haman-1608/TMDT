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
?>

<div class="tracuu mb-8"> 
    <form action="index.php" method="GET" class="flex flex-col gap-4 md:flex-row">
        <input type="hidden" name="page" value="donhang">
        
        <input 
            class="border border-gray-300 p-2 rounded-md flex-1"
            placeholder="Nhập mã đơn hàng (VD: 5)" 
            type="text" 
            name="order_code" 
            value="<?php echo isset($_GET['order_code']) ? htmlspecialchars($_GET['order_code']) : ''; ?>" 
            required
        >
        <input 
            class="border border-gray-300 p-2 rounded-md flex-1"
            placeholder="Nhập số điện thoại hoặc Email đặt hàng" 
            type="text" 
            name="contact_info"
            value="<?php echo isset($_GET['contact_info']) ? htmlspecialchars($_GET['contact_info']) : ''; ?>"
            required
        >
        <button 
            type="submit" 
            class="bg-blue-600 text-white px-6 py-2 rounded-md font-bold hover:bg-blue-700 transition"
        >
            TRA CỨU
        </button>
        </form>
</div>