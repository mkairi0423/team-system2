<?php
// ==================================================================================
// メール認証処理
// server/page/verify.php
// ==================================================================================

require_once __DIR__ . "/../../helpers/utils.php";

try {

    // トークン取得
    $token = $_GET['token'] ?? '';

    if ($token === '') {
        exit("認証トークンがありません。");
    }

    // DB接続
    $pdo = getPDO();

    // トークン検索
    $stmt = $pdo->prepare("
        SELECT *
        FROM user
        WHERE verification_token = :token
        LIMIT 1
    ");

    $stmt->bindValue(':token', $token, PDO::PARAM_STR);
    $stmt->execute();

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        exit("無効な認証URLです。");
    }

    // 有効期限チェック
    if (strtotime($user['token_expires']) < time()) {
        exit("認証URLの有効期限が切れています。");
    }

    // 認証済みに更新
    $stmt = $pdo->prepare("
        UPDATE user
        SET
            is_verified = 1,
            verification_token = NULL,
            token_expires = NULL
        WHERE user_id = :user_id
    ");

    $stmt->bindValue(':user_id', $user['user_id'], PDO::PARAM_INT);
    $stmt->execute();

} catch (PDOException $e) {

    exit("データベースエラー：" . $e->getMessage());

}

?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>メール認証完了</title>
    <link rel="shortcut icon" href="data:image/x-icon;,">
    <link rel="stylesheet" href="../../client/css/i">

    
    <!-- ③ 自動リダイレクト（3秒後にログインへ） -->
    <meta http-equiv="refresh" content="3;url=../../client/index.php">
</head>

<body>


    <div class="register-card">

        <div class="icon-area">
            <h1 class="logo">FridgeAI</h1>
        </div>

        <!-- ① 成功アイコン付き -->
        <h2>✅ メール認証が完了しました！</h2>

        <p>冷蔵庫AIをご利用いただけます。<br>
        3秒後自動的にログイン画面に移動します</p>

        <!-- ② ボタン -->
        <a href="../../client/index.php" class="btn-primary">
            ログイン画面へ
        </a>

    </div>

</body>

</html>