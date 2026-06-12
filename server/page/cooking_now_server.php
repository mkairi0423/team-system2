<?php
require_once '../helpers/utils.php';

header('Content-Type: application/json');

try {

    $pdo = getPDO();
    $user_id = 1;

    $input = json_decode(file_get_contents("php://input"), true);

    if (!$input || !isset($input['ingredients'])) {
        throw new Exception("食材データがありません");
    }

    $ingredients = $input['ingredients'];

    if (!is_array($ingredients) || empty($ingredients)) {
        throw new Exception("食材が空です");
    }

    $placeholders = implode(',', array_fill(0, count($ingredients), '?'));

    $sql = "UPDATE ingredients
            SET is_used = 1
            WHERE user_id = ?
            AND food IN ($placeholders)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_merge([$user_id], $ingredients));

    echo json_encode([
        "success" => true
    ]);

} catch (Exception $e) {

    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}
