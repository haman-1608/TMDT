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

<div class="w-full max-w-xl mx-auto px-4 mb-8">
        <div class="absolute top-0 left-0 w-full h-1.5 bg-gray-900"></div>

        <div class="p-8 sm:p-10">
            <div class="text-center mb-10">
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-gray-50 border border-gray-100 text-gray-900 mb-5 shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                </div>
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 tracking-tight mb-2 uppercase">
                    Tra Cứu Đơn Hàng Của Bạn
                </h1>
                <p class="text-gray-500 text-sm sm:text-base">
                    Nhập thông tin để kiểm tra trạng thái xử lý.
                </p>
            </div>

            <form action="index.php" method="GET" class="space-y-8">
                <input type="hidden" name="page" value="donhang">
                
                <div class="space-y-6">
                    <div class="group">
                        <label for="order_code" class="block text-xs font-bold text-gray-900 uppercase tracking-widest mb-2 ml-1">
                            Mã đơn hàng
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 text-gray-400 group-focus-within:text-gray-900 transition-colors duration-200"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22v-10"/></svg>
                            </div>
                            <input
                                type="text"
                                name="order_code"
                                id="order_code"
                                required
                                value="<?php echo isset($_GET['order_code']) ? htmlspecialchars($_GET['order_code']) : ''; ?>"
                                class="block w-full pl-10 pr-4 py-3.5 bg-gray-50 border border-gray-200 rounded-lg text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-gray-900 focus:border-gray-900 focus:bg-white transition-all duration-200 sm:text-sm"
                                placeholder="VD: 5"
                            >
                        </div>
                    </div>

                    <div class="group">
                        <label for="contact_info" class="block text-xs font-bold text-gray-900 uppercase tracking-widest mb-2 ml-1">
                            Thông tin liên hệ
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 text-gray-400 group-focus-within:text-gray-900 transition-colors duration-200"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            </div>
                            <input
                                type="text"
                                name="contact_info"
                                id="contact_info"
                                required
                                value="<?php echo isset($_GET['contact_info']) ? htmlspecialchars($_GET['contact_info']) : ''; ?>"
                                class="block w-full pl-10 pr-4 py-3.5 bg-gray-50 border border-gray-200 rounded-lg text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-gray-900 focus:border-gray-900 focus:bg-white transition-all duration-200 sm:text-sm"
                                placeholder="Email hoặc số điện thoại"
                            >
                        </div>
                    </div>
                </div>

                <div class="pt-2">
                    <button
                        type="submit"
                        class="group relative w-full flex justify-center items-center py-4 px-4 border border-transparent rounded-lg text-sm font-bold text-white bg-gray-900 hover:bg-black focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 shadow-xl shadow-gray-900/10 transition-all duration-200 transform hover:-translate-y-0.5"
                    >
                        <span class="tracking-wider uppercase">TRA CỨU NGAY</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ml-2 h-4 w-4 opacity-70 group-hover:translate-x-1 transition-transform"><path d="m9 18 6-6-6-6"/></svg>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>