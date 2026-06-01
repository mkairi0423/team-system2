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
    // DB接続
    $pdo = getPDO();

    // 従業員番号でユーザーを検索
    $sql = "SELECT * FROM employees WHERE emp_no = :emp_no LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(":emp_no", $emp_no, PDO::PARAM_STR);
    $stmt->execute();

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // ユーザーが存在しない
    if (!$user) {
        $_SESSION['emp_no_err'] = "従業員番号が間違っています。";
        header("Location: ../login.php");
        exit;
    }

    // パスワードチェック（ハッシュ前提）
    if (!password_verify($pass, $user['password'])) {
        $_SESSION['pass_err'] = "パスワードが間違っています。";
        header("Location: ../login.php");
        exit;
    }

    // ログイン成功 → セッションに保存
    $_SESSION['user'] = [
        'emp_no' => $user['emp_no'],
        'name' => $user['name']
    ];

    // ホーム画面へ
    header("Location: ../home.php");
    exit;

} catch (PDOException $e) {
    // DBエラー時
    error_log("DB Error: " . $e->getMessage());
    $_SESSION['db_err'] = "システムエラーが発生しました。";
    header("Location: ../login.php");
    exit;
}




// require_once  __DIR__ . "/../helpers/utils.php";

//ログイン画面（サーバーサイド）

?>