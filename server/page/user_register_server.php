<?php
// ==================================================================================
// auth/user_register_server.php （コントローラー側・修正版）
// ==================================================================================
require_once __DIR__ . "/../../helpers/utils.php";
require_once __DIR__ . "/../DB_function/user_register_DB.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ❌ POSTリクエスト以外は即座に弾く
if ($_SERVER['REQUEST_METHOD'] !== "POST") {
    nextpage("user_register");
    exit;
}

// ⚠️ セッション内の「過去のエラーメッセージ」だけをきれいに掃除
// unset($_SESSION['name_null_err'], $_SESSION['email_null_err'], $_SESSION['email_err']);
// unset($_SESSION['pass_null_err'], $_SESSION['pass_err'], $_SESSION['nameid_err'], $_SESSION['db_register_err']);

$has_error = false;

// ----------------------------------------------------------------------------------
// 🔏 1. ユーザー名（name_id）の厳格チェック
// ----------------------------------------------------------------------------------
$name = $_POST['name'] ?? "";
$name = trim($name);

if ($name === "") {
    $_SESSION['name_null_err'] = "ユーザーIDが空です。";
    $has_error = true;
} else {
    // if (!preg_match('/^[0-9]{5,}$/', $name)) {
    //     $_SESSION['nameid_err'] = "ユーザーIDは5桁以上で入力してください。";
    //     $has_error = true;
    // }
}

// ----------------------------------------------------------------------------------
// ✉️ 2. メールアドレスの厳格チェック
// ----------------------------------------------------------------------------------
$email = $_POST["email"] ?? "";
$email = trim($email);

if ($email === "") {
    $_SESSION['email_null_err'] = "メールアドレスが空です。";
    $has_error = true;
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
    $_SESSION['email_err'] = "正しいメールアドレスの形式にしてください。";
    $has_error = true;
}

// ----------------------------------------------------------------------------------
// 🔑 3. パスワードの厳格チェック
// ----------------------------------------------------------------------------------
$pass = $_POST['password'] ?? "";

if ($pass === "") {
    $_SESSION['pass_null_err'] = "パスワードが空です。";
    $has_error = true;
} else {
    $pass_length = strlen($pass);
    if ($pass_length < 8) {
        $_SESSION['pass_err'] = "パスワードは8文字以上にしてください。";
        $has_error = true;
    } elseif (preg_match('/^(?=.*[a-zA-Z])(?=.*[0-9]).+$/', $pass) === 0) {
        $_SESSION['pass_err'] = "パスワードは英字と数字を最低1文字ずつ含めてください。";
        $has_error = true;
    }
}

// 🛑 入力バリデーションで1つでも引っかかったら入力画面へ戻す
if ($has_error) {
    header("Location: ../../client/page/user_register.php");
    exit;
}

// 🟢 すべての入力が有効な場合は、DB登録処理を実行
$result = register_user($name, $email, $pass);

if ($result === true) {
    // 🚀 登録成功：ログイン処理（またはログイン画面へ遷移）
    login();
    exit;
} else {
    // ❌ DB接続エラーや重複エラーが発生した場合（戻り値の文字列をセッションに詰める）
    $_SESSION["db_register_err"] = $result;
    nextpage("user_register");
    exit;
}
