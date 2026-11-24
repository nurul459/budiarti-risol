
<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';

// Konfigurasi Gmail
define('EMAIL_PENGIRIM', 'delamareta11@gmail.com');
// EMAIL_APP_PASSWORD = App Password 16 karakter dari Google Account >
// Security > 2-Step Verification > App passwords. ISI SENDIRI
// sebelum dipakai - JANGAN commit App Password asli ke repo publik.
define('EMAIL_APP_PASSWORD', 'ISI_APP_PASSWORD_GMAIL_ANDA');

function kirimEmailReset($emailTujuan, $nama, $resetUrl)
{
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = EMAIL_PENGIRIM;
        $mail->Password   = str_replace(' ', '', EMAIL_APP_PASSWORD);
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->CharSet = 'UTF-8';

        $mail->setFrom(EMAIL_PENGIRIM, "Budiarti's Risol");
        $mail->addAddress($emailTujuan, $nama);

        $mail->isHTML(true);
        $mail->Subject = "Reset Password - Budiarti's Risol";

        $mail->Body = "
            <h2>Halo, " . htmlspecialchars($nama) . "!</h2>
            <p>Kami menerima permintaan untuk mengatur ulang password akun Anda.</p>
            <p>Klik tombol berikut untuk membuat password baru:</p>
            <p>
                <a href='" . htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8') . "'
                   style='background:#8b421c;color:white;padding:12px 20px;
                   text-decoration:none;border-radius:8px;'>
                   Reset Password
                </a>
            </p>
            <p>Tautan ini berlaku selama 1 jam.</p>
            <p>Jika Anda tidak meminta reset password, abaikan email ini.</p>
            <p>Salam,<br>Budiarti's Risol</p>
        ";

        $mail->AltBody = "Halo, $nama. Reset password melalui tautan berikut: $resetUrl. Tautan berlaku selama 1 jam.";

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("Gagal mengirim email reset: " . $mail->ErrorInfo);
        return false;
    }
}
