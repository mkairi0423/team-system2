<?php
// ==================================================================================
// DB_function/user_register_db.php （共通 getPDO() 活用版）
// ==================================================================================

// 💡 user_register_server.php で utils.php がすでに require_once されているため、
// getPDO() 関数がそのままここで呼び出せます。

/**
 * ユーザー登録のDB処理
 */

//TODO:debug 認証のところでエラーになっている
function register_user($name, $email, $password, $token, $expires)
{
    // セッションがまだ開始されていない場合のみ開始する安全設計
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    require_once __DIR__ . "/../../helpers/utils.php";

    // パスワードをハッシュ化
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // ユーザー情報をデータベースに挿入
    try {
        $pdo = getPDO();

        // 🟢 メール認証情報も一緒に保存
        $stmt = $pdo->prepare("
            INSERT INTO user
            (name, email, password, verification_token, token_expires, is_verified)
            VALUES
            (:name, :email, :password, :token, :expires, 0)
        ");

        // 型を明示して安全にバインド
        $stmt->bindValue(':name',     $name,            PDO::PARAM_STR);
        $stmt->bindValue(':email',    $email,           PDO::PARAM_STR);
        $stmt->bindValue(':password', $hashed_password, PDO::PARAM_STR);
        $stmt->bindValue(':token',    $token,           PDO::PARAM_STR);
        $stmt->bindValue(':expires',  $expires,         PDO::PARAM_STR);

        $stmt->execute();

        $_SESSION["log"] = "登録が成功しました";
        return true;

    } catch (PDOException $e) {

        // 登録エラー（例: メールアドレスやユーザーIDの重複）
        if ($e->getCode() == 23000) {
            $_SESSION["log"] = "このメールアドレスは既に登録されているか、入力値に問題があります。";
        } else {
            $_SESSION["log"] = "ユーザー登録中にエラーが発生しました。詳細: " . $e->getMessage();
        }

        return false;
    }
}

//TODO:　名前のIDとemailとpasswordだけでログインするよう
function register($name, $email, $password)
{
    // セッションがまだ開始されていない場合のみ開始する安全設計
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    require_once __DIR__ . "/../../helpers/utils.php";

    // パスワードをハッシュ化
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // ユーザー情報をデータベースに挿入
    try {
        $pdo = getPDO();

        // 🟢 メール認証情報も一緒に保存
        $stmt = $pdo->prepare("
            INSERT INTO user
            (name, email, password)
            VALUES
            (:name, :email, :password)
        ");

        $stmt->bindValue(':name',     $name,     PDO::PARAM_STR);
        $stmt->bindValue(':email',    $email,    PDO::PARAM_STR);
        $stmt->bindValue(':password', $hashed_password, PDO::PARAM_STR);

        $stmt->execute();

        $_SESSION["log"] = "登録が成功しました";
        echo $_SESSION['log'];
        // return true;
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $_SESSION["log"] = "このメールアドレスは既に登録されているか、入力値に問題があります。";
        } else {
            $_SESSION["log"] = "ユーザー登録中にエラーが発生しました。";
        }
        echo $_SESSION['log'];
        // return false;
    }
}