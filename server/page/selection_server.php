<?php
require_once __DIR__ . '/../../helpers/utils.php';

header('Content-Type: application/json; charset=UTF-8');

// ✅ エラー表示（開発中だけ）
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    // ✅ JSON受け取る
    $input = json_decode(file_get_contents("php://input"), true);

    if (!$input || !isset($input['recipe'])) {
        throw new Exception("データが不正です");
    }

    $recipe = $input['recipe'];
    $ingredients = $recipe['ingredients'] ?? [];

    if (empty($ingredients)) {
        throw new Exception("食材がありません");
    }

    $pdo = getPDO();
    $user_id = 1; // 仮

    // ✅ 在庫から一致する食材を取得
    $placeholders = implode(',', array_fill(0, count($ingredients), '?'));

    $sql = "SELECT * FROM ingredients
            WHERE user_id = ?
            AND food IN ($placeholders)
            AND is_used = 0";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_merge([$user_id], $ingredients));
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ✅ cooking_nowへ登録
    foreach ($results as $row) {
        $insert = $pdo->prepare("
            INSERT INTO cooking_now (
                user_id,
                original_ingredient_id,
                food,
                quantity,
                original_storage_type
            ) VALUES (?, ?, ?, ?, ?)
        ");

        $insert->execute([
            $user_id,
            $row['id'],
            $row['food'],
            $row['quantity'],
            $row['storage_type']
        ]);
    }

    // ✅ 成功レスポンス
    echo json_encode([
        "success" => true,
        "count" => count($results)
    ]);

} catch (Exception $e) {

    // ✅ エラー返す
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}