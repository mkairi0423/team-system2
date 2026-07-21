<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../../PHPMailer/src/Exception.php';
require __DIR__ . '/../../PHPMailer/src/PHPMailer.php';
require __DIR__ . '/../../PHPMailer/src/SMTP.php';


// ======================================================
// .env 読み込み関数
// ======================================================
function loadEnv(string $dir, string $file): void
{
    $path = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . $file;

    if (!file_exists($path)) {
        die('.env が見つかりません: ' . $path);
    }

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if ($line === '' || $line[0] === '#') continue;

        [$key, $value] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}

// ======================================================
// .env 読み込み
// ======================================================
loadEnv(__DIR__ . '/../..', '.env.email');


// ======================================================
// メール送信
// ======================================================
function sendVerifyMail($email, $name, $token)
{
    try {

        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->Host       = $_ENV['MAIL_HOST'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['MAIL_USER'];
        $mail->Password   = $_ENV['MAIL_PASS'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int)$_ENV['MAIL_PORT'];
        $mail->CharSet    = 'UTF-8';

        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ],
        ];

        $mail->setFrom($_ENV['MAIL_USER'], "FridgeAI");
        $mail->addAddress($email, $name);

        $verifyUrl = "http://localhost/team-system2/server/page/verify.php?token=" . $token;

$mail->isHTML(true);

$mail->Subject = "【FridgeAI】メールアドレス認証のご案内";

$mail->Body = "
<div style='font-family: Arial, sans-serif; color:#333; line-height:1.6; max-width:600px; margin:0 auto;'>

    <!-- ヘッダー -->
    <div style='text-align:center; padding:20px 0; border-bottom:1px solid #eee;'>
        <img src='cid:fridgeai_logo' alt='FridgeAI' style='height:40px;'>
    </div>

    <!-- 本文 -->
    <div style='padding:20px;'>

        <p>{$name} 様</p>

        <p>
            このたびは FridgeAI にご登録いただき、誠にありがとうございます。<br>
            ご登録いただいたメールアドレスの認証を行うため、以下のボタンよりお手続きをお願いいたします。
        </p>

        <p style='text-align:center; margin:30px 0;'>
            <a href='{$verifyUrl}'
               style='background:linear-gradient(135deg, #3b82f6, #06b6d4); color:#fff; padding:12px 20px;
                      text-decoration:none; border-radius:6px; display:inline-block;'>
                メールアドレスを認証する
            </a>
        </p>

        <hr style='border:none; border-top:1px solid #eee;'>

        <p style='font-size:12px; color:#666; margin-top:20px;'>
            ■ご案内<br>
            ・本URLの有効期限は24時間です。<br>
            ・期限切れの場合は再登録が必要です。<br>
            ・心当たりがない場合はこのメールを破棄してください。
        </p>

        <p style='font-size:12px; color:#999;'>
            本メールは送信専用です。<br>
            FridgeAI Team
        </p>

    </div>

</div>
";

        $mail->send();
    } catch (Exception $e) {
        die("メール送信エラー：" . $mail->ErrorInfo);
    }
}
