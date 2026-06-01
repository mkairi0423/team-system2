<?php
//ユーザー登録機能
//値が空とかユーザーIDがSTR型とかチェックは厳しめに

require_once __DIR__ . "/../helpers/utils.php";

session_start();


if ($_SERVER['REQUEST_METHOD'] !== "POST") {
    login();
    exit;
}

// $_SESSION = [];//TODO:これまずいかも

//　IDが空じゃないか
$name_id = $_POST['name_id'] ?? "";
$name_id = trim($name_id);
if (empty($name_id)) {
    $_SESSION['nameid_null_err'] = "ユーザーIDが空です。";
}

//メアドが空かと形式チェック
$email = $_POST["email"] ?? "";
if (empty($email)) {
    $_SESSION['email_null_err'] = "メールアドレスが空です。";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    // メアドの形式チェックを追加
    $_SESSION['email_err'] = "正しいメールアドレスの形式で入力してください。";
}

// パスワードは空じゃないか
$pass = $_POST['password'] ?? "";
if (empty($pass)) {
    $_SESSION['pass_null_err'] = "パスワードが空です。";
} else {
    // パスワードが8文字以上か
    if (strlen($pass) < 8) {
        $_SESSION['pass_err'] = "パスワードを8文字以上に設定してください。";
    }
    // 英数字混合か判断
    if (preg_match('/^(?=.*[a-zA-Z])(?=.*[0-9]).+$/', $pass) === 0) {
        $_SESSION['pass_err'] = "パスワードは英数字混合にしてください。";
    }
}


if (!preg_match('/^[0-9]{8}$/', $name_id) || !ctype_digit($name_id)) {
    $_SESSION['nameid_err'] = "ユーザーIDが正しくありません。";
}

//エラーメッセージがあったらHomeに移動
if (!empty($_SESSION)) {

    exit;
}

//DB
$pdo = null;
try {
    // データべースと接続
    $pdo = getPDO();

    //トランザクションの開始
    $pdo->beginTransaction();

    $hashed_password = password_hash($pass, PASSWORD_DEFAULT);

    //user情報の登録
    $sql = "INSERT INTO USERS(
                    name_id ,email ,password
                ) values (
                    :name_id ,:email ,:password
                                )";

    $stmt = $pdo->prepare($sql);
    //bindValueで型が正しいか確認
    
    $stmt->bindValue(':name_id', $name_id, PDO::PARAM_STR);
    $stmt->bindValue(':email', $email, PDO::PARAM_STR);
    $stmt->bindValue(':password', $hashed_password, PDO::PARAM_STR);

    //userに結果を格納
    $stmt->execute();

    //コミットして確定
    $pdo->commit();

    //登録成功したらログイン画面に遷移させる
    login();

    $stmt = null;
    $pdo = null;
    
    exit;
} catch (PDOException $poe) {

    // エラーが起きたらロールバック（巻き戻し）
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION["db_register_err"] = "DBエラー: " . $poe->getMessage();
    nextpage("emp_register");
    exit;
}

?>