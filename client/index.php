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
                <input name="name_id" placeholder="ユーザーID">
                <input type="password" name="password" placeholder="パスワード">
                <button class="btn-primary">ログイン</button>
            </form>

            <div class="sub-links">
                <a href="page/emp_register.php">アカウント作成</a>
                <a href="page/pass_reset.php">パスワードを忘れた方</a>
            </div>
        </div>
    </div>
    
</body>
</html>