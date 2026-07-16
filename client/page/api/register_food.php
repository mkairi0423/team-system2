<?php
// ===================================================
// page/api/register_food.php
// バーコード商品登録API
// ===================================================

session_start();

require_once __DIR__ . "/../../../helpers/def.php";

header("Content-Type: application/json; charset=UTF-8");

try {

    $pdo = new PDO(
        "mysql:host=" . DB_HOST .
            ";dbname=" . DB_NAME .
            ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    $data = json_decode(file_get_contents("php://input"), true);

    $sql = "
INSERT INTO ingredients
(
    user_id,
    category_id,
    storage_location_id,
    food_name,
    quantity,
    unit,
    expiration_date,
    term_type
)
VALUES
(
    :user_id,
    :category_id,
    :storage_location_id,
    :food_name,
    :quantity,
    :unit,
    :expiration_date,
    :term_type
)
";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ":user_id"             => $_SESSION["user_id"],
        ":category_id"         => 4, // その他（必要に応じて変更）
        ":storage_location_id" => 1, // 冷蔵庫（必要に応じて変更）
        ":food_name"           => $data["name"],
        ":quantity"            => preg_replace('/[^0-9]/', '', $data["quantity"]),
        ":unit"                => "ml",
        ":expiration_date"     => $data["expiration"],
        ":term_type"           => "賞味期限"
    ]);

    echo json_encode([
        "success" => true
    ]);
} catch (Exception $e) {

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage(),
        "trace" => $e->getTraceAsString()
    ]);
}
