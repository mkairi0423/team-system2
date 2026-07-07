<?php
// ==================================================================================
// server/user_login_server.php （コントローラー側 - セキュリティ強化版）
// ==================================================================================

require_once __DIR__ . "/../helpers/utils.php";
require_once __DIR__ . "/../server/DB_function/login_DB.php";

// セッションが開始されていない場合のみ開始
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

// 3. バリデーション（入力チェック：emptyから厳密な比較へ変更）
if ($name === "") {
    $_SESSION['name_err'] = "ユーザーIDが空です";
}

if ($pass === "") {
    $_SESSION['pass_err'] = "パスワードが空です";
}

// 1つでもエラーがあれば、この時点でフロントへ戻す
if (!empty($_SESSION['name_err']) || !empty($_SESSION['pass_err'])) {
    header("Location: ../client/index.php");
    exit;
}

// 4. データベース処理
$user = user_login($name);

// 【セキュリティ対策】エラーメッセージの統一（アカウントの存在を隠蔽）
$auth_error_msg = "ユーザーIDまたはパスワードが間違っています。";

// ユーザーが存在しない場合
if (!$user) {
    $_SESSION['pass_err'] = $auth_error_msg;
    header("Location: ../client/index.php");
    exit;
}

// 5. パスワードの照合直前に挿入
// --- デバッグ用出力 ---
// var_dump($pass);
// var_dump($user['password']);
// var_dump(password_verify($pass, $user['password']));
// exit; // 処理を止めて結果だけ見る
// --------------------

if (!password_verify($pass, $user['password'])) {
    $_SESSION['pass_err'] = $auth_error_msg;
    // header("Location: ../client/index.php");
exit;
}

// 【セキュリティ対策】セッション固定攻撃（セッションハイジャック）対策
// ログイン成功時に古いセッションIDを破棄し、新しいIDを生成する
session_regenerate_id(true);

// DBのuser_idをセッションのuser_idに保存
$_SESSION["user_id"] = $user["user_id"];

// 不要になった「古い入力値」のセッションをクリア
unset($_SESSION['old']);

// ホーム画面へリダイレクト
header("Location: ../client/page/home.php");
exit;