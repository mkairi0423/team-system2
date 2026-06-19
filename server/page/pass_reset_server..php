<?php
// ==================================================================================
// page/password_reset_server.php （最新DB構造・セッションバグ修正版）
// ==================================================================================

// 🟢 getPDO() や共通関数を使うための utils.php 読み込み
require_once __DIR__ . "/../../helpers/utils.php";

session_start();

// POST送信以外は受け付けない
if ($_SERVER['REQUEST_METHOD'] !== "POST") {
    header("Location: password_reset.php");
    exit;
}

// ⚠️ 修正：$_SESSION = []; で初期化すると、これからセットするエラーや
// ログイン中のセッションデータまで全消去されてしまうため、特定のエラーキーだけをリセットします。
unset($_SESSION['email_err']);
unset($_SESSION['reset_success']);
unset($_SESSION['db_err']);

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
try {
    // 🟢 もともと作ってある共通関数 getPDO() を使用
    $pdo = getPDO();

    // 🟢 変更：テーブル名を「USERS」から単数形の「user」へ、主キーを「user_id」へ修正
    $sql = "SELECT user_id, email FROM user WHERE email = :email LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':email', $email, PDO::PARAM_STR);
    $stmt->execute();

    $user = $stmt->fetch();

    if ($user) {
        // 【本来の処理】ランダムなトークン（URL）を発行し、DBに保存してメールを送信する箇所
        // $token = bin2hex(random_bytes(32));

        // セキュリティ対策（ユーザー存在の有無を推測させないためのメッセージ）
        $_SESSION['reset_success'] = "ご入力いただいたメールアドレスに再設定リンクを送信しました。";
    } else {
        // セキュリティのポイント: 登録がなくても「送信しました」と同じ画面を出す
        $_SESSION['reset_success'] = "ご入力いただいたメールアドレスに再設定リンクを送信しました。";
    }

    // 🟢 オブジェクトの開放はPHPが自動で行うため、そのままリダイレクトへ
    header("Location: password_reset.php");
    exit;
} catch (PDOException $poe) {
    $_SESSION["db_err"] = "システムエラーが発生しました。時間をおいて再度お試しください。";
    header("Location: password_reset.php");
    exit;
}
