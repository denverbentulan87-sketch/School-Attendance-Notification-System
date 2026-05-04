<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

function create_mailer(): PHPMailer {
    $mail = new PHPMailer(true);

    $mail->isSMTP();    
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'denverbentulan87@gmail.com';
    $mail->Password   = 'cnad rahg crml ptsq';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom('denverbentulan87@gmail.com', 'School Attendance System');
    return $mail;
}

/**
 * Send QR code image to newly registered student.
 */
function send_qr_email(string $student_email, string $student_name, string $qr_url): void {
    try {
        $mail = create_mailer();
        $mail->addAddress($student_email, $student_name);
        $mail->isHTML(true);
        $mail->Subject = 'Your Attendance QR Code — Save & Use at Gate Scanner';

        $qr_bytes = @file_get_contents($qr_url);

        if ($qr_bytes !== false) {
            $mail->addStringEmbeddedImage(
                $qr_bytes,
                'qr_code',
                'attendance_qr.png',
                'base64',
                'image/png'
            );
            $mail->addStringAttachment(
                $qr_bytes,
                'attendance_qr_' . preg_replace('/\s+/', '_', $student_name) . '.png',
                'base64',
                'image/png'
            );
            $img_tag = "<img src='cid:qr_code'
                             alt='Your Attendance QR Code'
                             style='width:260px;height:260px;border:4px solid #16a34a;
                                    border-radius:12px;display:block;margin:0 auto;'>";
        } else {
            $img_tag = "<img src='" . htmlspecialchars($qr_url) . "'
                             alt='Your Attendance QR Code'
                             style='width:260px;height:260px;border:4px solid #16a34a;
                                    border-radius:12px;display:block;margin:0 auto;'>";
        }

        $mail->Body = "
            <div style='font-family:sans-serif;max-width:520px;margin:auto;padding:32px;
                        border:1px solid #e2e8f0;border-radius:12px;background:#ffffff;'>
                <h2 style='color:#16a34a;margin-bottom:4px;'>Hello, {$student_name}!</h2>
                <p style='color:#64748b;margin-bottom:24px;font-size:14px;'>
                    Your unique attendance QR code is ready and <strong>attached to this email</strong>.
                    Please follow the instructions below.
                </p>
                <div style='text-align:center;margin:24px 0;'>
                    {$img_tag}
                </div>
                <div style='text-align:center;margin-bottom:24px;'>
                    <p style='background:#dcfce7;color:#15803d;font-weight:700;font-size:14px;
                               padding:12px 20px;border-radius:8px;display:inline-block;margin:0;'>
                        📎 Your QR code is also attached to this email as a PNG file.<br>
                        <span style='font-weight:400;font-size:13px;'>
                            Open the attachment and save it to your phone's gallery.
                        </span>
                    </p>
                </div>
                <div style='background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;
                            padding:18px;margin-bottom:20px;'>
                    <p style='color:#15803d;font-weight:700;margin-bottom:10px;font-size:14px;'>
                        📋 How to use your QR code:
                    </p>
                    <ol style='color:#166534;font-size:13px;line-height:2;padding-left:18px;'>
                        <li>Open the <strong>attached PNG file</strong> in this email</li>
                        <li>Save it to your phone's gallery / camera roll</li>
                        <li>Every school day, open the saved QR image on your phone</li>
                        <li>Show it to the <strong>gate scanner camera</strong> at the entrance</li>
                        <li>Wait for the green ✅ confirmation — you're marked present!</li>
                    </ol>
                </div>
                <div style='background:#fef2f2;border:1px solid #fecaca;border-radius:8px;
                            padding:12px 16px;margin-bottom:20px;'>
                    <p style='color:#dc2626;font-size:13px;margin:0;'>
                        <strong>⚠️ Important:</strong> Do not share this QR code with anyone.
                        Each QR code is unique to you and tied to your attendance record.
                    </p>
                </div>
                <p style='color:#94a3b8;font-size:12px;text-align:center;margin:0;'>
                    — School Attendance System (EduTrack)
                </p>
            </div>
        ";
        $mail->AltBody = "Hello {$student_name}, your QR code is attached to this email as a PNG file. Save it to your phone and show it at the gate scanner every day to mark your attendance.";
        $mail->send();

    } catch (Exception $e) {
        error_log("QR email error [{$student_email}]: " . $e->getMessage());
    }
}

/**
 * STEP 1: Probe Gmail's SMTP in real-time to check if the inbox exists.
 */
function verify_gmail_exists(string $email): bool {
    $sender = 'denverbentulan87@gmail.com';

    try {
        $socket = @stream_socket_client(
            'tcp://smtp.gmail.com:587',
            $errno, $errstr, 10,
            STREAM_CLIENT_CONNECT
        );

        if (!$socket) {
            error_log("verify_gmail_exists: cannot connect to smtp.gmail.com — $errstr");
            return true;
        }

        stream_set_timeout($socket, 10);

        $read = function() use ($socket): string {
            $response = '';
            while ($line = fgets($socket, 512)) {
                $response .= $line;
                if (isset($line[3]) && $line[3] === ' ') break;
            }
            return $response;
        };

        $write = function(string $cmd) use ($socket): void {
            fwrite($socket, $cmd . "\r\n");
        };

        $read();
        $write("EHLO verify.local");
        $read();
        $write("STARTTLS");
        $read();
        stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        $write("EHLO verify.local");
        $read();
        $write("AUTH LOGIN");
        $read();
        $write(base64_encode($sender));
        $read();
        $write(base64_encode('cnad rahg crml ptsq'));
        $authResponse = $read();

        if (strpos($authResponse, '235') === false) {
            error_log("verify_gmail_exists: SMTP auth failed");
            fwrite($socket, "QUIT\r\n");
            fclose($socket);
            return true;
        }

        $write("MAIL FROM:<{$sender}>");
        $read();
        $write("RCPT TO:<{$email}>");
        $rcpt = $read();
        $write("QUIT");
        fclose($socket);

        if (strpos($rcpt, '250') !== false) {
            return true;
        }

        error_log("verify_gmail_exists: [{$email}] rejected — " . trim($rcpt));
        return false;

    } catch (\Throwable $e) {
        error_log("verify_gmail_exists exception: " . $e->getMessage());
        return true;
    }
}

/**
 * Send a 6-digit OTP to a verified Gmail inbox.
 */
function send_otp_email(string $to, string $name, string $otp): bool {
    if (!verify_gmail_exists($to)) {
        error_log("send_otp_email: [{$to}] does not exist — blocked before sending.");
        return false;
    }

    try {
        $mail = create_mailer();
        $mail->addAddress($to, $name);
        $mail->isHTML(true);
        $mail->Subject = 'Your Verification Code — School Attendance System';
        $mail->Body    = "
            <div style='font-family:sans-serif;max-width:480px;margin:auto;padding:32px;
                        border:1px solid #e2e8f0;border-radius:12px;'>
                <h2 style='color:#2d3748;margin-bottom:8px;'>Email Verification</h2>
                <p style='color:#718096;margin-bottom:24px;'>
                    Hello <strong>" . htmlspecialchars($name) . "</strong>,<br>
                    Use the code below to complete your registration.
                    This code expires in <strong>10 minutes</strong>.
                </p>
                <div style='background:#ebf8ff;border-radius:10px;padding:20px;text-align:center;'>
                    <span style='font-size:40px;font-weight:800;letter-spacing:12px;color:#2b6cb0;'>
                        {$otp}
                    </span>
                </div>
                <p style='color:#a0aec0;font-size:13px;margin-top:24px;'>
                    If you did not request this, you can safely ignore this email.
                </p>
                <p style='color:#7f8c8d;font-size:13px;'>— School Attendance System</p>
            </div>
        ";
        $mail->AltBody = "Hello {$name}, your verification code is: {$otp} (expires in 10 minutes)";
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("OTP email send error [{$to}]: " . $e->getMessage());
        return false;
    }
}

/**
 * Notify parent of their auto-created account credentials.
 */
function send_parent_welcome_email(
    string $parent_email,
    string $parent_name,
    string $student_name,
    string $temp_password
): void {
    try {
        $mail = create_mailer();
        $mail->addAddress($parent_email, $parent_name);
        $mail->isHTML(true);
        $mail->Subject = 'Your Parent Account — School Attendance System';
        $mail->Body    = "
            <div style='font-family:sans-serif;max-width:500px;margin:auto;padding:32px;
                        border:1px solid #e2e8f0;border-radius:12px;'>
                <h2 style='color:#2c3e50;'>Welcome, {$parent_name}!</h2>
                <p>A parent account has been created for you linked to your child
                   <strong>{$student_name}</strong>.</p>
                <p>Use the credentials below to log in:</p>
                <table style='width:100%;border-collapse:collapse;margin:16px 0;'>
                    <tr>
                        <td style='padding:8px;background:#f8f9fa;font-weight:bold;'>Email</td>
                        <td style='padding:8px;'>{$parent_email}</td>
                    </tr>
                    <tr>
                        <td style='padding:8px;background:#f8f9fa;font-weight:bold;'>Temporary Password</td>
                        <td style='padding:8px;'><strong>{$temp_password}</strong></td>
                    </tr>
                </table>
                <p style='color:#e74c3c;'>Please log in and change your password immediately.</p>
                <p style='color:#7f8c8d;font-size:13px;'>— School Attendance System</p>
            </div>
        ";
        $mail->AltBody = "Welcome {$parent_name}! Login: {$parent_email} / Temp password: {$temp_password}. Please change it after logging in.";
        $mail->send();
    } catch (Exception $e) {
        error_log("Parent welcome email error [{$parent_email}]: " . $e->getMessage());
    }
}

/**
 * Notify parent when student scans QR — status: present or late.
 * $scan_time is nullable — if null/empty, time row is hidden in the email.
 */
function send_attendance_notification(
    string $parent_email,
    string $student_name,
    string $status,
    ?string $scan_time = null
): void {
    // If status is absent, delegate to the dedicated absent function
    if ($status === 'absent') {
        send_absent_notification($parent_email, $student_name);
        return;
    }

    try {
        $mail         = create_mailer();
        $date_display = date('F j, Y');

        // Only show time row if scan_time is a valid non-empty value
        $has_time     = !empty($scan_time) && $scan_time !== 'N/A';
        $time_display = $has_time ? date('h:i A', strtotime($scan_time)) : null;

        $mail->addAddress($parent_email);
        $mail->isHTML(true);

        if ($status === 'present') {
            $mail->Subject = "✅ {$student_name} has arrived at school";
            $color         = '#16a34a';
            $label         = 'Present';
            $note          = '';
        } else {
            $mail->Subject = "⚠️ {$student_name} arrived late at school";
            $color         = '#d97706';
            $label         = 'Late';
            $note          = "
                <p style='color:#92400e;font-size:13px;background:#fef3c7;
                           border:1px solid #fde68a;border-radius:8px;
                           padding:10px 14px;margin-bottom:16px;'>
                    Please ensure your child arrives before <strong>8:10 AM</strong>
                    to be marked present.
                </p>";
        }

        $time_row = $time_display ? "
            <tr>
                <td style='color:#64748b;padding:6px 0;'>Time scanned</td>
                <td style='color:#1e293b;font-weight:600;padding:6px 0;'>
                    {$time_display}
                </td>
            </tr>" : '';

        $mail->Body = "
            <div style='font-family:sans-serif;max-width:520px;margin:auto;padding:32px;
                        border:1px solid #e2e8f0;border-radius:12px;background:#ffffff;'>
                <h2 style='color:{$color};margin-bottom:4px;'>Attendance Update</h2>
                <p style='color:#64748b;font-size:14px;margin-bottom:20px;'>{$date_display}</p>

                <div style='background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;
                            padding:18px;margin-bottom:20px;'>
                    <table style='width:100%;border-collapse:collapse;font-size:14px;'>
                        <tr>
                            <td style='color:#64748b;padding:6px 0;width:40%;'>Student</td>
                            <td style='color:#1e293b;font-weight:600;padding:6px 0;'>
                                {$student_name}
                            </td>
                        </tr>
                        <tr>
                            <td style='color:#64748b;padding:6px 0;'>Status</td>
                            <td style='padding:6px 0;'>
                                <span style='background:{$color};color:#fff;font-size:12px;
                                             font-weight:700;padding:3px 10px;
                                             border-radius:99px;'>
                                    {$label}
                                </span>
                            </td>
                        </tr>
                        {$time_row}
                    </table>
                </div>

                {$note}

                <p style='color:#94a3b8;font-size:12px;text-align:center;margin:24px 0 0;'>
                    — School Attendance System (EduTrack)
                </p>
            </div>
        ";
        $time_alt      = $time_display ? " at {$time_display}" : '';
        $mail->AltBody = "Attendance update for {$student_name}: {$label}{$time_alt} on {$date_display}.";
        $mail->send();

    } catch (Exception $e) {
        error_log("Attendance notification error [{$parent_email}]: " . $e->getMessage());
    }
}

/**
 * Notify parent when student has no scan record — status: absent.
 * Called by notify_absent.php via cron/scheduler or manually from notifications tab.
 */
function send_absent_notification(
    string $parent_email,
    string $student_name
): void {
    try {
        $mail         = create_mailer();
        $date_display = date('F j, Y');

        $mail->addAddress($parent_email);
        $mail->isHTML(true);
        $mail->Subject = "❌ {$student_name} was absent today — {$date_display}";
        $mail->Body    = "
            <div style='font-family:sans-serif;max-width:520px;margin:auto;padding:32px;
                        border:1px solid #e2e8f0;border-radius:12px;background:#ffffff;'>
                <h2 style='color:#dc2626;margin-bottom:4px;'>Absence Notice</h2>
                <p style='color:#64748b;font-size:14px;margin-bottom:20px;'>{$date_display}</p>

                <div style='background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;
                            padding:18px;margin-bottom:20px;'>
                    <table style='width:100%;border-collapse:collapse;font-size:14px;'>
                        <tr>
                            <td style='color:#64748b;padding:6px 0;width:40%;'>Student</td>
                            <td style='color:#1e293b;font-weight:600;padding:6px 0;'>
                                {$student_name}
                            </td>
                        </tr>
                        <tr>
                            <td style='color:#64748b;padding:6px 0;'>Status</td>
                            <td style='padding:6px 0;'>
                                <span style='background:#dc2626;color:#fff;font-size:12px;
                                             font-weight:700;padding:3px 10px;
                                             border-radius:99px;'>
                                    Absent
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td style='color:#64748b;padding:6px 0;'>Date</td>
                            <td style='color:#1e293b;font-weight:600;padding:6px 0;'>
                                {$date_display}
                            </td>
                        </tr>
                    </table>
                </div>

                <div style='background:#fef2f2;border:1px solid #fecaca;border-radius:8px;
                            padding:12px 16px;margin-bottom:20px;'>
                    <p style='color:#991b1b;font-size:13px;margin:0;'>
                        No QR scan was recorded for your child today. If this is an error
                        or your child was present, please contact the school immediately.
                    </p>
                </div>

                <p style='color:#94a3b8;font-size:12px;text-align:center;margin:0;'>
                    — School Attendance System (EduTrack)
                </p>
            </div>
        ";
        $mail->AltBody = "{$student_name} was recorded as absent on {$date_display}. If this is an error, please contact the school.";
        $mail->send();

    } catch (Exception $e) {
        error_log("Absent notification error [{$parent_email}]: " . $e->getMessage());
    }
}
?>