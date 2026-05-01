<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

function create_mailer(): PHPMailer {
    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';       // or your SMTP host
    $mail->SMTPAuth   = true;
    $mail->Username   = 'denverbentulan87@gmail.com'; // your Gmail
    $mail->Password   = 'cnad rahg crml ptsq';    // Gmail App Password (not your login password)
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom('denverbentulan87@gmail.com', 'School Attendance System');
    return $mail;
}

/**
 * Send QR code image to newly registered student
 */
function send_qr_email(string $student_email, string $student_name, string $qr_url): void {
    try {
        $mail = create_mailer();
        $mail->addAddress($student_email, $student_name);
        $mail->isHTML(true);
        $mail->Subject = 'Your Attendance QR Code';
        $mail->Body    = "
            <div style='font-family:sans-serif;max-width:500px;margin:auto;'>
                <h2 style='color:#2c3e50;'>Hello, {$student_name}!</h2>
                <p>Your unique QR code for daily attendance has been generated.</p>
                <p>Scan this QR code within the required time window each day to mark your attendance.</p>
                <div style='text-align:center;margin:24px 0;'>
                    <img src='{$qr_url}' alt='QR Code' style='width:250px;height:250px;border:4px solid #2c3e50;border-radius:8px;'>
                </div>
                <p style='color:#e74c3c;'><strong>Do not share this QR code with anyone.</strong></p>
                <p style='color:#7f8c8d;font-size:13px;'>— School Attendance System</p>
            </div>
        ";
        $mail->AltBody = "Hello {$student_name}, your QR code: {$qr_url}";
        $mail->send();
    } catch (Exception $e) {
        error_log("QR email error [{$student_email}]: " . $mail->ErrorInfo);
    }
}


/**
 * Notify parent when student misses the scan window
 * Called from cron_notify.php
 */
function notify_parent_absent(string $parent_email, string $student_name, string $date): void {
    try {
        $mail = create_mailer();
        $mail->addAddress($parent_email);
        $mail->isHTML(true);
        $mail->Subject = "⚠️ Attendance Alert: {$student_name} — {$date}";
        $mail->Body    = "
            <div style='font-family:sans-serif;max-width:500px;margin:auto;'>
                <h2 style='color:#e74c3c;'>Attendance Notice</h2>
                <p>Dear Parent/Guardian,</p>
                <p>We would like to inform you that:</p>
                <table style='width:100%;border-collapse:collapse;margin:16px 0;'>
                    <tr>
                        <td style='padding:8px;background:#f8f9fa;font-weight:bold;'>Student</td>
                        <td style='padding:8px;'>{$student_name}</td>
                    </tr>
                    <tr>
                        <td style='padding:8px;background:#f8f9fa;font-weight:bold;'>Date</td>
                        <td style='padding:8px;'>{$date}</td>
                    </tr>
                    <tr>
                        <td style='padding:8px;background:#f8f9fa;font-weight:bold;'>Status</td>
                        <td style='padding:8px;color:#e74c3c;'><strong>Did not scan attendance QR</strong></td>
                    </tr>
                </table>
                <p>Please follow up with your child regarding their attendance.</p>
                <p style='color:#7f8c8d;font-size:13px;'>— School Attendance System</p>
            </div>
        ";
        $mail->AltBody = "{$student_name} did not scan their attendance QR on {$date}.";
        $mail->send();
    } catch (Exception $e) {
        error_log("Parent notify error [{$parent_email}]: " . $mail->ErrorInfo);
    }
}
?>