<?php
require_once __DIR__ . '/../../helpers/utils.php';

header('Content-Type: application/json; charset=UTF-8');

try {
    $input = json_decode(file_get_contents("php://input"), true);
    if (!$input || !isset($input['recipe'])) {
        throw new Exception("データが不正です");
    }

    $recipe = $input['recipe'];
    // 💡 AIルールで定義した出力フォーマットのused_ingredients配列を参照
    $ingredients = $recipe['used_ingredients'] ?? [];

    if (empty($ingredients)) {
        throw new Exception("食材がありません");
    }

    $pdo = getPDO();
    $user_id = 1;

    // 💡 修正1: 食材名ではなく ID で検索するように変更
    $ingredient_ids = array_column($ingredients, 'id');
    $placeholders = implode(',', array_fill(0, count($ingredient_ids), '?'));

    $sql = "SELECT id, food_name, quantity, storage_location_id 
            FROM ingredients
            WHERE user_id = ? AND id IN ($placeholders) AND is_used = 0";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_merge([$user_id], $ingredient_ids));
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ✅ cooking_nowへ登録
    $insert = $pdo->prepare("
        INSERT INTO cooking_now (
            user_id,
            original_ingredient_id,
            food,
            quantity,
            original_storage_location_id
        ) VALUES (?, ?, ?, ?, ?)
    ");

    foreach ($results as $row) {
        $insert->execute([
            $user_id,
            $row['id'],
            $row['food_name'],
            $row['quantity'],
            $row['storage_location_id'] // 💡 文字列ではなく数値IDを保存
        ]);
    }

    echo json_encode(["success" => true, "count" => count($results)]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}