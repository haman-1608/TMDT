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

<h1 style="text-align: center;">TRA CỨU ĐƠN HÀNG</h1>


<form style="display: flex; flex-direction:column; gap: 20%" action="">
    <input type="text" name="order-code">
    <input type="text" name="order-number">
</form>