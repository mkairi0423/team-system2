<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../helpers/gemini_api.php';
require_once __DIR__ . '/../../helpers/utils.php';

try {
    $userId = $_GET['user_id'] ?? 1;
    $keyword = $_GET['keyword'] ?? '';

    if (empty($keyword)) {
        throw new Exception("キーワードが指定されていません。");
    }

    // 1. AIからレシピに必要な食材リストを取得
    // ※ ここでAPI制限(429)などの例外が発生すると catch に飛びます
    $neededFoods = getNeededIngredientsFromAI($keyword);

    // 2. DBから現在の在庫を取得
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT food_name, quantity, unit FROM ingredient WHERE user_id = ?");
    $stmt->execute([$userId]);
    $userStocks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stockMap = [];
    foreach ($userStocks as $s) {
        $stockMap[$s['food_name']] = $s;
    }

    $results = [];

    // 3. AIの食材リストをベースに、在庫状況を付与
    foreach ($neededFoods as $foodName) {
        $inStock = false;
        $quantity = 0;
        $unit = "";

        foreach ($stockMap as $name => $data) {
            if (mb_strpos($name, $foodName) !== false || mb_strpos($foodName, $name) !== false) {
                $inStock = true;
                $quantity = $data['quantity'];
                $unit = $data['unit'];
                break;
            }
        }

        $results[] = [
            "id" => $foodName, // フロント側のキー合わせ
            "food_name" => $foodName,
            "in_stock" => $inStock,
            "quantity" => (float) $quantity,
            "unit" => $unit
        ];
    }

    echo json_encode(["success" => true, "ingredients" => $results], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    // 429エラーかどうかをメッセージで判定（API側で投げられる例外の種類に合わせて調整してください）
    $errorMessage = $e->getMessage();
    if (strpos($errorMessage, '429') !== false) {
        http_response_code(429);
    } else {
        http_response_code(500);
    }

    echo json_encode([
        "success" => false,
        "message" => $errorMessage
    ], JSON_UNESCAPED_UNICODE);
}