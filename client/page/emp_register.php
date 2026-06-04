<?php
//ユーザー情報の登録画面
//TODO:
// ユーザー登録処理

$errors = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $fridge_type = $_POST["fridge_type"] ?? "";

    // バリデーション
    if ($name === "") $errors[] = "ユーザー名を入力してください";
    if ($email === "") $errors[] = "メールアドレスを入力してください";
    if ($password === "") $errors[] = "パスワードを入力してください";
    if ($fridge_type === "") $errors[] = "冷蔵庫タイプを選択してください";

    // エラーなしなら保存（仮）
    if (empty($errors)) {

        // パスワードはハッシュ化（重要）
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // 仮保存（本来はDB）
        $user = [
            "name" => $name,
            "email" => $email,
            "password" => $hashedPassword,
            "fridge_type" => $fridge_type
        ];

        // ファイル保存（簡易）
        $file = "users.json";

        $users = [];
        if (file_exists($file)) {
            $users = json_decode(file_get_contents($file), true) ?? [];
        }

        $users[] = $user;

        file_put_contents($file, json_encode($users, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        // 登録後リダイレクト
        header("Location: login.php");
        exit;
    }
}
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

            <form action="register.php" method="POST">

                <div class="form-group">
                    <label>ユーザー名</label>
                    <input type="text" name="name" placeholder="山田 太郎" required>
                </div>

                <div class="form-group">
                    <label>メールアドレス</label>
                    <input type="email" name="email" placeholder="example@mail.com" required>
                </div>

                <div class="form-group">
                    <label>パスワード</label>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>

                <div class="form-group">
                    <label>冷蔵庫タイプ</label>

                    <div class="select-wrapper">
                        <select name="fridge_type" required>

                            <option value="home">🏠 家庭用（スタンダード）</option>
                            <option value="large">🏢 業務用（大型）</option>
                            <option value="mini">🍱 一人暮らし・ミニ冷蔵庫</option>
                            <option value="smart">🤖 スマート冷蔵庫（IoT対応）</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn">
                    登録する
                </button>

            </form>

            <p class="login-link">
                すでにアカウントがある？ <a href="../index.php">ログイン</a>
            </p>

        </div>

    </div>

</body>

</html>