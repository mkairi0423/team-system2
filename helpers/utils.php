<?php

require_once __DIR__ . "/def.php";



/**
 * h
 * htmlspecialcharsの省略
 */
function h($str)
{
    return htmlspecialchars($str, ENT_QUOTES, "UTF-8");
}

/**
 * getPDO() 関数：
 * MySQL に接続する PDO オブジェクトを作成して返します。
 * static $pdo を使うことで、同じリクエスト内で PDO 接続を一度だけ作成します。
 * モダン PHP 風に PDO 作成時にオプションを設定しています。
 */

function getPDO()
{
    // すでに PDO 接続が存在する場合はそのまま返す
    static $pdo = null;
    if ($pdo != null) return $pdo;

    // DSN (Data Source Name) を作成
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    try {
        // PDO オブジェクトを作成（オプション付き）
        // PDO::ATTR_ERRMODE => 例外を投げるモード
        // PDO::ATTR_EMULATE_PREPARES => 本物のプリペアドステートメントを使用
        // PDO::ATTR_DEFAULT_FETCH_MODE => デフォルトで連想配列で取得
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        // 作成した PDO を返す
        return $pdo;
        // return "登録完了";//debug用
    } catch (PDOException $e) {
        // 接続失敗時はスクリプトを停止してエラーを表示
        die("データベースに接続できませんでした。エラー：" . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
    }
}
?>