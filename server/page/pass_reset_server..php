<?php
//パスワードリセットする画面のサーバー側

// パスワードリセット用のサーバー側処理

require_once __DIR__ . "/../../helpers/utils.php";

session_start();

// POST送信以外は受け付けない
if ($_SERVER['REQUEST_METHOD'] !== "POST") {
    header("Location: password_reset.php"); // この画面自身のファイル名に合わせてください
    exit;
}

// セッションエラーの初期化
$_SESSION = [];

// 入力値の取得とクレンジング
$email = $_POST["email"] ?? "";
$email = trim($email);

// --- 1. バリデーションチェック ---
if (empty($email)) {
    $_SESSION['email_err'] = "メールアドレスを入力してください。";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['email_err'] = "正しいメールアドレスの形式で入力してください。";
}

// エラーがあれば元の画面に戻す
if (!empty($_SESSION['email_err'])) {
    header("Location: password_reset.php");
    exit;
}

// --- 2. データベース照合 ---
$pdo = null;

try {
    $pdo = getPDO();

    // 入力されたメールアドレスを持つユーザーが登録されているか確認
    $sql = "SELECT name_id, email FROM USERS WHERE email = :email LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':email', $email, PDO::PARAM_STR);
    $stmt->execute();

    $user = $stmt->fetch();

    if ($user) {
        // 【本来の処理】
        // ここでランダムなトークン（URL）を発行し、DB（password_resetsテーブル等）に保存してメールを送信します。
        // $token = bin2hex(random_bytes(32));

        // セキュリティ対策（ユーザー存在の有無を推測させないためのメッセージ）
        $_SESSION['reset_success'] = "ご入力いただいたメールアドレスに再設定リンクを送信しました。";
    } else {
        // 💡 セキュリティのポイント:
        // 「そのメールアドレスは登録されていません」と出すと、悪意ある人にアカウントの有無を特定される（列挙攻撃）ため、
        // 登録がなくても「送信しました」と同じ画面を出すのが一般的で安全です。
        $_SESSION['reset_success'] = "ご入力いただいたメールアドレスに再設定リンクを送信しました。";
    }

    $stmt = null;
    $pdo = null;

    header("Location: password_reset.php");
    exit;
} catch (PDOException $poe) {
    $_SESSION["db_err"] = "システムエラーが発生しました。時間をおいて再度お試しください。";
    // 開発時のデバッグ用： $_SESSION["db_err"] = "DBエラー: " . $poe->getMessage();
    header("Location: password_reset.php");
    exit;
}
