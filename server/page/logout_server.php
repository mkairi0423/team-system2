<?php
//ログアウトする処理

require_once __DIR__ . "/../../helpers/def.php";
session_start();

//セッションの削除
$_SESSION = [];
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 999999, '/');
}
//サーバー側のセッションデータを破棄
session_destroy();

//index.php(ホーム画面)に遷移
if (empty($_SESSION)) {
    header("Location: " . TEAM_SYSTEM2 . "/client/index.php");
} else {
    echo "エラーが発生しました。";
}
