<?php
//cooking_server.php
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
            // 古い調理中データをクリア
            $pdo->prepare("DELETE FROM cooking_now WHERE user_id = ?")->execute([$user_id]);
 
            foreach ($items as $item) {
                $stmt = $pdo->prepare("SELECT * FROM ingredients WHERE id = ?");
                $stmt->execute([$item['id']]);
                $ing = $stmt->fetch();
                if (!$ing) continue;
 
                // 【変更点】フロントから受け取ったquantityをそのまま使用
                // ロジックを介さず「選択した量＝消費量」とする
                $used = (float)($item['quantity'] ?? 0);
               
                // 万が一在庫以上の数値が送られてきた場合の安全策
                $used = min($used, (float)$ing['quantity']);
 
                // 在庫から差し引き
                $pdo->prepare("UPDATE ingredients SET quantity = quantity - ? WHERE id = ?")
                    ->execute([$used, $ing['id']]);
               
                // 調理中テーブルへ記録
                $pdo->prepare("INSERT INTO cooking_now (user_id, original_ingredient_id, food, quantity, unit, original_storage_location_id, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())")
                    ->execute([$user_id, $ing['id'], $ing['food_name'], $used, $ing['unit'], $ing['storage_location_id']]);
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
                // 在庫に戻す
                $pdo->prepare("UPDATE ingredients SET quantity = quantity + ? WHERE id = ?")
                    ->execute([$item['quantity'], $item['original_ingredient_id']]);
                $pdo->prepare("DELETE FROM cooking_now WHERE id = ?")->execute([$cooking_id]);
            }
            $pdo->commit();
            echo json_encode(['success' => true]);
            break;
 
        case 'complete':
            $user_id = $input['user_id'] ?? null;
            $pdo->beginTransaction();
            // 一時保管データを取得
            $stmt = $pdo->prepare("SELECT * FROM cooking_now WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $items = $stmt->fetchAll();
           
            $stmtHist = $pdo->prepare("INSERT INTO cooking_history (user_id, dish_name, used_food_name, quantity, unit, cooked_at) VALUES (?, ?, ?, ?, ?, NOW())");
            foreach ($items as $item) {
                $stmtHist->execute([$user_id, '料理', $item['food'], $item['quantity'], $item['unit']]);
            }
            // 調理中リストを消去
            $pdo->prepare("DELETE FROM cooking_now WHERE user_id = ?")->execute([$user_id]);
            $pdo->commit();
            echo json_encode(['success' => true]);
            break;
    }
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}