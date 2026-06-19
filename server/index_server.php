<?php
// ==================================================================================
// server/user_login_server.php （コントローラー側）
// ==================================================================================

require_once __DIR__ . "/../helpers/utils.php";
// 🟢 作成したDB関数ファイルを読み込む（パスは実際の環境に合わせて調整してください）
require_once __DIR__ . "/../DB_function/login_DB.php";

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
    $_SESSION['name_err'] = "ユーザーIDが空です";
}

if (empty($pass)) {
    $_SESSION['pass_err'] = "パスワードが空です";
} elseif (strlen($pass) <= 7) {
    $_SESSION['pass_err'] = "パスワードを8文字以上で入力してください";
}

// 1つでもエラーがあれば、この時点でフロントへ戻す
if (!empty($_SESSION['name_err']) || !empty($_SESSION['pass_err'])) {
    header("Location: ../client/index.php");
    exit;
}

// 4. データベース処理（🟢 関数を呼び出すだけのシンプルな形に！）
$user = user_login($name);

// ユーザーが存在しない場合
if (!$user) {
    $_SESSION['pass_err'] = "ユーザーIDまたはパスワードが間違っています。";
    header("Location: ../client/index.php");
    exit;
}

// 5. パスワードの照合（DB内のハッシュ値と、入力された生パスワードを比較）
if (!password_verify($pass, $user['password'])) {
    $_SESSION['pass_err'] = "ユーザーIDまたはパスワードが間違っています。";
    header("Location: ../client/index.php");
    exit;
}

// 6. ログイン成功処理
// 🟢 セッションの「uid」に格納する値を、新しいスキーマ名「user_id」に完全修正！
$_SESSION['user'] = [
    'uid'  => $user['user_id'], // 👈 旧 $user['uid'] から修正
    'name' => $user['name']
];

// 不要になった「古い入力値」のセッションをクリア
unset($_SESSION['old']);

// ホーム画面へリダイレクト
header("Location: ../client/page/home.php");
exit;
