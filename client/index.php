<?php
require_once __DIR__ . "/../helpers/utils.php";

session_start();
$name_err = $_SESSION['name_id_err'] ?? "";
$pass_err = $_SESSION['pass_err'] ?? "";
unset($_SESSION['name_id_err']);
unset($_SESSION['pass_err']);

?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ログインページ</title>
    <link rel="stylesheet" href="css/index.css">
</head>

<body>
    <div class="login-container">
        <div class="login-card">
            <div class="icon-area">
                <!-- <img src="images/Image.jpg" alt="Frigge Icon" class="icon"> -->
                <h1>FridgeAI</h1>
            </div>

            <form action="../server/index_server.php" method="POST">
                <div class="form-group">
                    <input name="name_id" placeholder="ユーザーID">
                    <?php if (!empty($name_err)): ?>
                        <p class="err-msg"><?= h($name_err) ?></p>
                    <?php endif; ?>
                </div>
                <div class= "form-group" >
                    <input type="password" name="password" placeholder="パスワード">
                    <?php if (!empty($pass_err)): ?>
                        <p class="err-msg">
                            <?= h($pass_err) ?>
                        </p>
                    <?php endif; ?>
                </div>
                <button class="btn-primary">ログイン</button>
            </form>

            <div class="sub-links">
                <a href="page/user_register.php">アカウント作成</a>
                <a href="page/pass_reset.php">パスワードを忘れた方</a>
            </div>
        </div>
    </div>

</body>

</html>