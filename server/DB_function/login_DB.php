<?php
// ==================================================================================
// DB_function/user_login_db.php （ログイン用DB取得処理）
// ==================================================================================

/**
 * ユーザーID（name）をキーに、userテーブルから1件取得する
 * @param string $name ユーザーが入力したネームID
 * @return array|false 成功時: ユーザー情報の連想配列, 失敗・不在時: false
 */

//index_serever.phpで使われている
function user_login($name)
{
    require_once __DIR__ . "/../../helpers/utils.php";
    try {
        $pdo = getPDO();

        // カラム名を name で検索
        $sql = "SELECT * FROM user WHERE name = :name LIMIT 1";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(":name", $name, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("DB Login Error: " . $e->getMessage());
        return false;
    }
}
