<?php
require_once __DIR__ . "/../DB_function/connect.php"; // DB接続ファイル

if (!isset($_GET['token'])) {
    exit("無効なアクセスです。");
}

$token = $_GET['token'];

try {

    // トークンを検索
    $sql = "SELECT user_id
            FROM user
            WHERE verification_token = ?
              AND token_expires > NOW()
              AND is_verified = 0";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$token]);

    $user = $stmt->fetch();

    if (!$user) {
        exit("認証URLが無効、または有効期限が切れています。");
    }

    // 認証済みに更新
    $sql = "UPDATE user
            SET
                is_verified = 1,
                verification_token = NULL,
                token_expires = NULL
            WHERE user_id = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user['user_id']]);

    echo "メール認証が完了しました。ログインしてください。";

} catch (PDOException $e) {
    echo "エラー：" . $e->getMessage();
}
?>