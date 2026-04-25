<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

function sendInvoiceEmail($to, $orderData) {
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = '7cellectronic@gmail.com';
        $mail->Password = 'your-app-password-here';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';
        
        $mail->setFrom('7cellectronic@gmail.com', '7Cellectronic Store');
        $mail->addAddress($to);
        
        $mail->isHTML(true);
        $mail->Subject = 'Invoice Pesanan #' . $orderData['order_id'] . ' - 7Cellectronic';
        $mail->Body = generateInvoiceHTML($orderData);
        $mail->AltBody = "Invoice Pesanan #" . $orderData['order_id'] . "\n\nTotal: Rp " . number_format($orderData['total_harga'], 0, ',', '.');
        
        $mail->send();
        return ['success' => true, 'message' => 'Invoice berhasil dikirim'];
        
    } catch (Exception $e) {
        error_log("Email Error: " . $mail->ErrorInfo);
        return ['success' => false, 'message' => "Gagal kirim email: " . $mail->ErrorInfo];
    }
}

function generateInvoiceHTML($data) {
    $tanggal = date('d F Y, H:i', strtotime($data['created_at'] ?? 'now'));
    
    $payment_labels = [
        'transfer_bca' => 'BCA Transfer',
        'transfer_mandiri' => 'Mandiri Transfer',
        'transfer_bni' => 'BNI Transfer',
        'gopay' => 'GoPay',
        'ovo' => 'OVO',
        'dana' => 'DANA',
        'shopeepay' => 'ShopeePay',
        'qris' => 'QRIS',
        'cod' => 'Cash on Delivery (COD)'
    ];
    
    $payment_display = $payment_labels[$data['payment_method']] ?? $data['payment_method'];
    
    $html = '<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #' . $data['order_id'] . '</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background: #f8f9fa; }
        .container { max-width: 600px; margin: 20px auto; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #0d6efd, #0a58ca); color: white; padding: 25px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; }
        .content { padding: 25px; }
        .invoice-info { background: #f8f9fa; padding: 15px; border-radius: 6px; margin-bottom: 20px; }
        .invoice-info table { width: 100%; border-collapse: collapse; }
        .invoice-info td { padding: 8px 0; border-bottom: 1px solid #dee2e6; }
        .invoice-info td:last-child { text-align: right; font-weight: 500; }
        .product-detail { margin: 20px 0; padding: 15px; background: #e7f1ff; border-left: 4px solid #0d6efd; border-radius: 0 4px 4px 0; }
        .total-section { text-align: right; margin: 25px 0; padding: 15px; background: #d1e7dd; border-radius: 6px; }
        .total-section .amount { font-size: 28px; font-weight: bold; color: #198754; }
        .footer { text-align: center; padding: 20px; background: #f8f9fa; color: #6c757d; font-size: 13px; border-top: 1px solid #dee2e6; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>7Cellectronic</h1>
            <p>Invoice Pesanan #' . $data['order_id'] . '</p>
            <p style="font-size: 14px; margin-top: 8px;">' . $tanggal . '</p>
        </div>
        
        <div class="content">
            <p>Halo <strong>' . htmlspecialchars($data['customer_name']) . '</strong>,</p>
            <p>Terima kasih telah berbelanja di <strong>7Cellectronic</strong>! Berikut adalah detail pesanan Anda:</p>
            
            <div class="invoice-info">
                <table>
                    <tr><td>Nomor Invoice</td><td>#' . $data['order_id'] . '</td></tr>
                    <tr><td>Tanggal</td><td>' . $tanggal . '</td></tr>
                    <tr><td>Email</td><td>' . htmlspecialchars($data['email']) . '</td></tr>
                    <tr><td>Metode Pembayaran</td><td>' . $payment_display . '</td></tr>
                    <tr><td>Status</td><td><span style="color: #198754; font-weight: 500;">Menunggu Konfirmasi</span></td></tr>
                </table>
            </div>
            
            <div class="product-detail">
                <strong>Produk:</strong> ' . htmlspecialchars($data['product_name']) . '<br>
                <strong>Varian:</strong> ' . htmlspecialchars($data['varian'] ?? '-') . '<br>
                <strong>Jumlah:</strong> ' . $data['qty'] . ' unit<br>
                <strong>Alamat Pengiriman:</strong><br>
                ' . htmlspecialchars($data['alamat']) . '
            </div>
            
            <div class="total-section">
                <div style="font-size: 14px; color: #666; margin-bottom: 5px;">Total Pembayaran</div>
                <div class="amount">Rp ' . number_format($data['total_harga'], 0, ',', '.') . '</div>
            </div>
            
            <p style="font-size: 13px; color: #6c757d; border-top: 1px solid #dee2e6; padding-top: 15px;">
                <strong>Butuh Bantuan?</strong><br>
                Hubungi kami: support@7cellectronic.com<br>
                Simpan email ini sebagai bukti transaksi yang sah.
            </p>
        </div>
        
        <div class="footer">
            &copy; 2024 7Cellectronic. All rights reserved.
        </div>
    </div>
</body>
</html>';
    
    return $html;
}
?>