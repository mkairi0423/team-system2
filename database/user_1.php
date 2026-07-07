一人目のユーザー
<?php
require_once __DIR__ . "/../server/DB_function/user_register_db.php";

$name = "test2026";
$email = "test@example.com";
$password = "test2026";


register($name, $email, $password);