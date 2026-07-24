<?php
// ===================================================
// page/api/register_food.php
// バーコード商品登録API
// ===================================================

session_start();

require_once __DIR__ . "/../../../helpers/def.php";
require_once __DIR__ . "/../../../helpers/utils.php";

header("Content-Type: application/json; charset=UTF-8");

try {

    $pdo = getPDO();

    // トランザクション開始
    $pdo->beginTransaction();

    $data = json_decode(file_get_contents("php://input"), true);

    // 数量を取得（空なら1）
    $quantity = preg_replace('/[^0-9]/', '', $data["quantity"] ?? '');

    if ($quantity === '') {
        $quantity = 1;
    }

    $sql = "
    INSERT INTO ingredient
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
        ":category_id"         => 4,
        ":storage_location_id" => 1,
        ":food_name"           => $data["name"] ?? '',
        ":quantity"            => (int)$quantity,
        ":unit"                => "ml",
        ":expiration_date"     => $data["expiration"] ?? null,
        ":term_type"           => "賞味期限"
    ]);

    // 登録されたIDを取得しておく
    $lastId = $pdo->lastInsertId();

    // コミット
    $pdo->commit();

    // 成功レスポンス（1回だけ出力する）
    echo json_encode([
        "success" => true,
        "message" => "登録しました。",
        "lastId"  => $lastId
    ]);

} catch (Exception $e) {

    // トランザクション中ならロールバック
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // エラー時のレスポンス（HTTPステータスコード500を設定しておくとより親切です）
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}