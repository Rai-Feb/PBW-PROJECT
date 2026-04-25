<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

function sendInvoiceEmail($to, $orderData)
{
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = '7cellectronic@gmail.com'; // Ganti email Anda
        $mail->Password = 'password_aplikasi_gmail'; // Ganti App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('7cellectronic@gmail.com', '7Cellectronic Store');
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = 'Invoice Pesanan #' . $orderData['order_id'];

        $body = "
        <h2>Invoice Pesanan #{$orderData['order_id']}</h2>
        <p>Halo {$orderData['customer_name']},</p>
        <p>Terima kasih telah berbelanja di 7Cellectronic.</p>
        <hr>
        <p><strong>Produk:</strong> {$orderData['product_name']}</p>
        <p><strong>Total:</strong> Rp " . number_format($orderData['total_harga'], 0, ',', '.') . "</p>
        <p><strong>Status:</strong> Menunggu Konfirmasi Admin</p>
        <hr>
        <p>Silakan login ke akun Anda untuk melihat status pengiriman.</p>
        ";

        $mail->Body = $body;
        $mail->send();
        return ['success' => true];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $mail->ErrorInfo];
    }
}
?>