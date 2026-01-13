<?php
ob_start();
session_start();
include_once "admin/config/dbconnect.php";

// Khởi tạo giỏ hàng nếu chưa có
if(!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

// Xử lý thêm vào giỏ hàng
if(isset($_POST['add_to_cart'])){
    $id = $_POST['id'];
    $name = $_POST['name'];
    $price = $_POST['price'];
    $imgs = $_POST['imgs'];
    $quantity = (isset($_POST['quantity'])) ? intval($_POST['quantity']) : 1;
    
    $check = false;

    foreach ($_SESSION['cart'] as $index => $sp) {
        if ($sp['id'] == $id) {
            $_SESSION['cart'][$index]['quantity'] += $quantity;
            $check = true;
            break;
        }
    }

    if(!$check){
        $sp = array(
            'id' => $id, 
            'name' => $name, 
            'price' => $price, 
            'imgs' => $imgs, 
            'quantity' => $quantity
        );
        $_SESSION['cart'][] = $sp;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="css/style.css">
    <link rel="stylesheet" type="text/css" href="css/search.css">
    <link rel="stylesheet" type="text/css" href="css/tracuu.css">
    <link rel="stylesheet" type="text/css" href="css/trangchu.css">
    <link rel="stylesheet" type="text/css" href="css/danhmucsp.css">
    <link rel="stylesheet" type="text/css" href="css/gtvalh.css">
    <link rel="stylesheet" type="text/css" href="css/chitiet.css">
    <link rel="stylesheet" type="text/css" href="css/giohang.css">
    <link rel="icon" type="image/png" href="imgs/Logo1.png">
    <script type="text/javascript" src="javascript/check.js"></script>
    <script src="jquery-3.7.1.js"></script>
    <title>Good Optic - Nhìn rõ hôm nay, tự tin ngày mai</title>
</head>
<body>
    
    <?php include("components/header.php"); ?>
        
    <main>
        <?php
            $page = $_GET['page'] ?? 'trangchu';
            $file = "pages/{$page}.php";
            
            if (file_exists($file)) {
                include($file);
            } else {
                include("pages/trangchu.php");
            }
        ?>
    </main>

    <?php include("components/footer.php"); ?>
</body>
</html>
