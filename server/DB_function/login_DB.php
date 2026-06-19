<?php
// ==================================================================================
// DB_function/user_login_db.php （ログイン用DB取得処理）
// ==================================================================================

/**
 * ユーザー名（name）をキーに、userテーブルから1件取得する
 * @param string $name ユーザーID（ユーザー名）
 * @return array|false 成功時: ユーザー情報の連想配列, 失敗・不在時: false
 */
function user_login($name)
{
    require_once __DIR__ . "/../../helpers/utils.php";
    try {
        $pdo = getPDO();

        // 🟢 テーブル名を「users」から新しい単数形の「user」に修正
        $sql = "SELECT * FROM user WHERE name = :name LIMIT 1";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(":name", $name, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // エラーログに詳細を記録し、コントローラー側にはfalseを返す（または例外を再スロー）
        error_log("DB Login Error: " . $e->getMessage());
        return false;
    }
}
