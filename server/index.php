<?php
require_once __DIR__ . "/../helpers/utils.php";

session_start();

if ($_SERVER['REQUEST_METHOD'] !== "POST") {
    exit;
}

//　IDが空じゃないか
$emp_no = $_POST['emp_no'] ?? "";
if (empty($emp_no)) {
    $_SESSION['emp_no_err'] = "従業員番号が空です。";
}

// パスワードは空じゃないか
$pass = $_POST['password'] ?? "";
if (empty($pass)) {
    $_SESSION['pass_err'] = "パスワードが空です。";
}

//英数字混合か判断 preg_matchは英数字が含まれてたら1 含まれていなかったら0を返す
// if (!preg_match('/^(?=.*[a-zA-Z])(?=.*[0-9]).+$/', $inputs['password']) === 0) {
//     $_SESSION['pass_err'] = "パスワードは英数字混合です。";
// }

// パスワードが8文字以上か
if (strlen(trim($pass)) <= 7) {
    // if (empty($_SESSION['pass_err'])) {
    $_SESSION['pass_err'] = "パスワードを8文字以上に設定してください";
    // }
}

//エラーメッセージがあったらHomeに移動
if (!empty($_SESSION['emp_err']) || !empty($_SESSION['emp_no_err']) || !empty($_SESSION['pass_err'])) {
        
    exit;
}

try {
    // DB登録
    $pdo = getPDO();

//ログイン画面（サーバーサイド）
} catch(PDOException $e){
    
}

?>
