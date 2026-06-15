<?php
// ==================================================================================
// DB_function/user_register_db.php （共通 getPDO() 活用版）
// ==================================================================================

// 💡 user_register_server.php で utils.php がすでに require_once されているため、
// getPDO() 関数がそのままここで呼び出せます。

/**
 * ユーザー登録のDB処理
 */
function register_user($name, $email, $password)
{
    session_start();

    require_once __DIR__ . "/../../helpers/utils.php";

    // パスワードをハッシュ化
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // ユーザー情報をデータベースに挿入
    try {
        
        $pdo = getPDO();

        $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (:name, :email, :password)");

        // 型を明示して安全にバインド
        $stmt->bindValue(':name',     $name,            PDO::PARAM_STR);
        $stmt->bindValue(':email',    $email,           PDO::PARAM_STR);
        $stmt->bindValue(':password', $hashed_password, PDO::PARAM_STR);

        $stmt->execute();
        $_SESSION["log"] = "登録が成功しました";
        return true;
    } catch (PDOException $e) {
        // 登録エラー（例: メールアドレスやユーザーIDの重複）
        if ($e->getCode() == 23000) {
            $_SESSION["log"] = "ユーザー登録中にエラーが発生しました。詳細: " . $e->getMessage();
        }
        return false;
    }
}
