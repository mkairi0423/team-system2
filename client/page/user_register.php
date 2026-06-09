<?php
//ユーザー情報の登録画面
session_start();

$pass_err = $_SESSION['pass_err'] ?? "";
$email_null_err = $_SESSION['email_null_err'] ?? "";
$email_err = $_SESSION['email_err'] ?? "";
$name_null_err = $_SESSION['name_null_err'] ?? "";



//debug用
// $db_register_err = $_SESSION['db_register_err'] ?? "";
// echo $db_register_err;

unset($_SESSION['pass_err'], $_SESSION['email_null_err'], $_SESSION['email_err'], $_SESSION['name_null_err']);


?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>新規登録 | 冷蔵庫AIアシスタント</title>
    <link rel="stylesheet" href="../css/index.css">

</head>

<body>
    <div class="register-wrapper">

        <div class="register-card">
            <div class="logo">
                🧊 冷蔵庫AIアシスタント<br>
                FridgeAI
            </div>

            <h2>新規アカウント作成</h2>
            <p class="sub">あなたの冷蔵庫をスマート管理</p>

            <form action="../../server/page/user_register_server.php" method="POST">

                <div class="form-group">
                    <label>ユーザー名</label>
                    <input type="text" name="name" placeholder="例: user_name" required>
                    <?php if (!empty($name_null_err)): ?>
                        <p class="err-msg"><?= htmlspecialchars($name_null_err, ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label>メールアドレス</label>
                    <input type="email" name="email" placeholder="example@mail.com" required>
                    <?php if (!empty($email_null_err)): ?>
                        <p class="err-msg"><?= htmlspecialchars($email_null_err, ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                    <?php if (!empty($email_err)): ?>
                        <p class="err-msg"><?= htmlspecialchars($email_err, ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label>パスワード</label>
                    <input type="password" name="password" placeholder="••••••••" autocomplete="new-password" required>
                    <?php if (!empty($pass_err)): ?>
                        <p class="err-msg"><?= htmlspecialchars($pass_err, ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                </div>


                <!-- TODO:ここは考え中 -->
                <!-- <div class="form-group">
                    <label>冷蔵庫タイプ</label>

                    <div class="select-wrapper">
                        <select name="fridge_type" required>

                            <option value="home">🏠 家庭用（スタンダード）</option>
                            <option value="large">🏢 業務用（大型）</option>
                            <option value="mini">🍱 一人暮らし・ミニ冷蔵庫</option>
                            <option value="smart">🤖 スマート冷蔵庫（IoT対応）</option>
                        </select>
                    </div>
                </div> -->

                <button type="submit" class="btn">
                    登録する
                </button>

            </form>

            <p class="login-link">
                すでにアカウントをお持ちの方 <a href="../index.php">ログイン</a>
            </p>
        </div>

    </div>

</body>

</html>