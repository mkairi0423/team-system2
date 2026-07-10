一人目のユーザー
<?php
require_once __DIR__ . "/../server/DB_function/user_register_db.php";

$name = "test2026"; // 1番目のユーザーのユーザーID
$email = "test@example.com"; //  1番目のユーザーのemail
$password = "test2026"; //  1番目のユーザーのpassword


register($name, $email, $password);//認証なしでアカウント登録