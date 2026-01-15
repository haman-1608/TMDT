<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function guiMailThanhToan($data) {
    require "PHPMailer/src/Exception.php";
    require "PHPMailer/src/PHPMailer.php";
    require "PHPMailer/src/SMTP.php";

    $config = include(__DIR__ . '/mailconfig.php');
    $mail = new PHPMailer(true);

    try { 
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $config['email'];
        $mail->Password   = $config['password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        // 1. Tạo HTML danh sách sản phẩm từ giỏ hàng thực tế
        $productListHtml = "";
        foreach ($data['cart'] as $sp) {
            $thanhTien = number_format($sp['price'] * $sp['quantity'], 0, ',', '.');
            $giaLe = number_format($sp['price'], 0, ',', '.');
            $productListHtml .= "
            <tr>
                <td style='padding: 10px; border-bottom: 1px solid #eee;'>
                    <strong>{$sp['name']}</strong>
                </td>
                <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: center;'>{$sp['quantity']}</td>
                <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: right;'>{$thanhTien}đ</td>
            </tr>";
        }

        // 2. Đọc file template
        // Lưu ý: Đảm bảo đường dẫn file templatemail.html chính xác
        $emailBody = file_get_contents(__DIR__ . '/templatemail.html');

        // 3. Thay thế các biến (Placeholders)
        $emailBody = str_replace('{{customer_name}}', $data['customer_name'], $emailBody);
        $emailBody = str_replace('{{order_id}}', $data['order_id'], $emailBody);
        $emailBody = str_replace('{{date}}', date('d/m/Y H:i'), $emailBody);
        $emailBody = str_replace('{{address}}', $data['address'], $emailBody);
        $emailBody = str_replace('{{phone}}', $data['phone'], $emailBody);
        $emailBody = str_replace('{{product_list}}', $productListHtml, $emailBody);
        $emailBody = str_replace('{{shipping_fee}}', number_format($data['shipping_fee'], 0, ',', '.'), $emailBody);
        $emailBody = str_replace('{{discount}}', number_format($data['discount'], 0, ',', '.'), $emailBody);
        $emailBody = str_replace('{{total_price}}', number_format($data['total_all'], 0, ',', '.'), $emailBody);

        // 4. Cấu hình gửi mail
        $mail->setFrom($config['email'], 'GOOD OPTIC');
        $mail->addAddress($data['email'], $data['customer_name']);
        $mail->isHTML(true);
        $mail->Subject = 'Thông báo xác nhận đơn hàng #DH-' . $data['order_id'];
        $mail->Body    = $emailBody;

        $mail->send();
        return true;
    } catch (Exception $e) { 
        return false;
    }
} 