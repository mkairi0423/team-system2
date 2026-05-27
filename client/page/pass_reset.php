<?php
// HTMLを表示する前にセッションからメッセージを取得
session_start();
$email_err = $_SESSION['email_err'] ?? '';
$reset_success = $_SESSION['reset_success'] ?? '';
$db_err = $_SESSION['db_err'] ?? '';

// 表示し終わったらセッションをクリア
unset($_SESSION['email_err']);
unset($_SESSION['reset_success']);
unset($_SESSION['db_err']);

?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>パスワードを忘れた方</title>
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/password_reset.css">
</head>

<body>
    <div class="login-container">
        <div class="login-card">

            <h2>パスワード再設定</h2>

            <?php if (!empty($db_err)): ?>
                <span class="err-msg err-msg-top"><?= htmlspecialchars($db_err, ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>

            <?php if (!empty($reset_success)): ?>
                <div class="success-msg"><?= htmlspecialchars($reset_success, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <p>登録しているメールアドレスを入力してください。</p>

            <form action="../../server/page/pass_reset_server..php" method="POST">
                <label for="email">メールアドレス</label>
                <input type="email" id="email" name="email" placeholder="example@mail.com" required>

                <?php if (!empty($email_err)): ?>
                    <span class="err-msg"><?= htmlspecialchars($email_err, ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>

                <button type="submit" class="btn-primary mt-15">再設定リンクを送信</button>
            </form>

            <div class="sub-links">
                <a href="index.php">ログインに戻る</a>
                <a href="emp_register.php">アカウント作成</a>
            </div>

        </div>
    </div>
</body>

</html>