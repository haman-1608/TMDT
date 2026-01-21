<?php
session_start();
//kiem tra neu khong co session user thi khogn cho phep vao trang quan tri
// chuyenr qua trang login.php
if (!isset($_SESSION["user"])) {
  header("Location: login.php");
  die();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Trang quản trị GoodOptic</title>
  <link rel="stylesheet" href="./css/style.css">
  <link rel="stylesheet" href="./css/profile.css">
  <link rel="stylesheet" href="./css/chart.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
  <link rel="icon" type="image/png" href="../imgs/Logo1.png">
  <!-- <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/morris.js/0.5.1/morris.css"> -->

</head>
<?php require "./topbar.php"; ?>