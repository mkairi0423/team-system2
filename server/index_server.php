<?php
// ==================================================================================
// server/user_login_server.php （コントローラー側）
// ==================================================================================

require_once __DIR__ . "/../helpers/utils.php";
require_once __DIR__ . "/../server/DB_function/login_DB.php";

session_start();

// 1. セッションエラー・古い入力値の初期化
unset($_SESSION['name_err'], $_SESSION['pass_err'], $_SESSION['db_err']);

// POST送信以外は即座にアクセス拒否
if ($_SERVER['REQUEST_METHOD'] !== "POST") {
    header("Location: ../client/index.php");
    exit;
}

// 2. 入力値の取得と前後の余計なスペースの削除
$name = trim($_POST['name'] ?? "");
$pass = trim($_POST['password'] ?? "");

// 入力値をセッションに保持
$_SESSION['old'] = [
    'name' => $name
];

// 3. バリデーション（入力チェック）
if (empty($name)) {
    $_SESSION['name_err'] = "ユーザー名が空です"; // テーブルの「name」に合わせ表記をユーザー名に統一
}

if (empty($pass)) {
    $_SESSION['pass_err'] = "パスワードが空です";
}

// 1つでもエラーがあれば、この時点でフロントへ戻す
if (!empty($_SESSION['name_err']) || !empty($_SESSION['pass_err'])) {
    header("Location: ../client/index.php");
    exit;
}

// 4. データベース処理
$user = user_login($name);

// ユーザーが存在しない場合
if (!$user) {
    $_SESSION['pass_err'] = "ユーザー名またはパスワードが間違っています。";
    header("Location: ../client/index.php");
    exit;
}

// 5. パスワードの照合
if (!password_verify($pass, $user['password'])) {
    $_SESSION['pass_err'] = "ユーザー名またはパスワードが間違っています。";
    header("Location: ../client/index.php");
    exit;
}
$_SESSION['user'] = [
    'user_id'  => $user['user_id'],
    'name' => $user['name']
];

// 不要になった「古い入力値」のセッションをクリア
unset($_SESSION['old']);

// ホーム画面へリダイレクト
header("Location: ../client/page/home.php");
exit;
