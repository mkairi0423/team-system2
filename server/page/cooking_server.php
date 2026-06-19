<?php
header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = new PDO('mysql:host=localhost;dbname=team_system2;charset=utf8mb4', 'root', 'root', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'DB接続エラー']);
    exit;
}

$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true) ?? [];

try {
    switch ($action) {
        case 'start':
            $user_id = $input['user_id'] ?? null;
            $items = $input['items'] ?? [];
            
            $pdo->beginTransaction();
            $pdo->prepare("DELETE FROM cooking_now WHERE user_id = ?")->execute([$user_id]);

            foreach ($items as $item) {
                // 修正: テーブル名は 'ingredient', IDは 'ingredient_id' を使用
                $stmt = $pdo->prepare("SELECT * FROM ingredient WHERE ingredient_id = ?");
                $stmt->execute([$item['id']]);
                $ing = $stmt->fetch();
                if (!$ing) continue;

                $used = (float)($item['quantity'] ?? 0);
                $used = min($used, (float)$ing['quantity']);

                // 修正: UPDATEのWHERE句とカラム名を正しく指定
                $pdo->prepare("UPDATE ingredient SET quantity = quantity - ? WHERE ingredient_id = ?")
                    ->execute([$used, $ing['ingredient_id']]);
                
                // 修正: INSERTのカラム名に合わせる
                $pdo->prepare("INSERT INTO cooking_now (user_id, original_ingredient_id, food, quantity, unit, original_storage_location_id, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())")
                    ->execute([$user_id, $ing['ingredient_id'], $ing['food_name'], $used, $ing['unit'], $ing['storage_location_id']]);
            }
            $pdo->commit();
            echo json_encode(['success' => true]);
            break;

        case 'return_item':
            $cooking_id = $input['cooking_id'] ?? null;
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT * FROM cooking_now WHERE id = ?");
            $stmt->execute([$cooking_id]);
            $item = $stmt->fetch();
            if ($item) {
                // 修正: 在庫テーブル名とIDカラムを修正
                $pdo->prepare("UPDATE ingredient SET quantity = quantity + ? WHERE ingredient_id = ?")
                    ->execute([$item['quantity'], $item['original_ingredient_id']]);
                $pdo->prepare("DELETE FROM cooking_now WHERE id = ?")->execute([$cooking_id]);
            }
            $pdo->commit();
            echo json_encode(['success' => true]);
            break;

        case 'complete':
            $user_id = $input['user_id'] ?? null;
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT * FROM cooking_now WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $items = $stmt->fetchAll();
            
            $stmtHist = $pdo->prepare("INSERT INTO cooking_history (user_id, dish_name, used_food_name, quantity, unit, cooked_at) VALUES (?, ?, ?, ?, ?, NOW())");
            foreach ($items as $item) {
                $stmtHist->execute([$user_id, '料理', $item['food'], $item['quantity'], $item['unit']]);
            }
            $pdo->prepare("DELETE FROM cooking_now WHERE user_id = ?")->execute([$user_id]);
            $pdo->commit();
            echo json_encode(['success' => true]);
            break;
    }
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}