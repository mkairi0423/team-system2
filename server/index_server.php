<?php
require_once __DIR__ . "/../helpers/utils.php";

session_start();

unset($_SESSION['name_id_err'], $_SESSION['pass_err']);

if ($_SERVER['REQUEST_METHOD'] !== "POST") {
    exit;
}

// 入力値取得
$name_id = trim($_POST['name_id'] ?? "");
$pass = trim($_POST['password'] ?? "");

// 入力値を保持（ここが超重要）
$_SESSION['old'] = [
    'name_id' => $name_id
];

//　IDが空じゃないか
$name_id = $_POST['name_id'] ?? "";
if (empty($name_id)) {
    $_SESSION['name_id_err'] = "ユーザーIDが空です";
}

// パスワードは空じゃないか
$pass = $_POST['password'] ?? "";
if (empty($pass)) {
    $_SESSION['pass_err'] = "パスワードが空です";
}

// パスワードが8文字以上か
if (!empty($pass) && strlen(trim($pass)) <= 7) {
    $_SESSION['pass_err'] = "パスワードを8文字以上に設定してください";
}

//エラーメッセージがあったら戻る
if (!empty($_SESSION['name_id_err']) || !empty($_SESSION['pass_err'])) {
    header("Location: ../client/index.php");
    exit;
}

try {
    // DB接続
    $pdo = getPDO();

    // name_idでユーザーを検索
    $sql = "SELECT * FROM users WHERE name_id = :name_id LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(":name_id", $name_id, PDO::PARAM_STR);
    $stmt->execute();

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // ユーザーが存在しない
    if (!$user) {
        $_SESSION['name_id_err'] = "ユーザーIDが間違っています。";
        header("Location: ../client/index.php");
        exit;
    }

    // パスワードチェック（ハッシュ前提）
    if (
        !password_verify($pass, $user['password']) &&
        $pass !== $user['password']
    ) {
        $_SESSION['pass_err'] = "パスワードが間違っています。";
        header("Location: ../client/index.php");
        exit;
    }

    // ログイン成功 → セッションに保存
    $_SESSION['user'] = [
        'uid' => $user['uid'],
        'name_id' => $user['name_id'],
        'email' => $user['email']
    ];

    // ホーム画面へ
    header("Location: ../client/page/home.php");
    exit;

} catch (PDOException $e) {
    // DBエラー時
    error_log("DB Error: " . $e->getMessage());
    $_SESSION['db_err'] = "システムエラーが発生しました。";
    header("Location: ../client/index.php");
    exit;
}

?>