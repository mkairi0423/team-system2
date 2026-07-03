<?php
//cooking_sever.php
header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = new PDO('mysql:host=localhost;dbname=team_system2;charset=utf8mb4', 'root', 'root', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    $action = $_GET['action'] ?? '';
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $user_id = isset($input['user_id']) ? (int) $input['user_id'] : 1;

    switch ($action) {
case 'start':
            $pdo->beginTransaction(); // トランザクション開始
            try {
                if (!empty($input['items'])) {
                    foreach ($input['items'] as $item) {
                        $target_id = (int)($item['id'] ?? 0);
                        // 名前からIDを検索する処理
                        if ($target_id === 0) {
                            $stmt = $pdo->prepare("SELECT ingredient_id FROM ingredient WHERE food_name = ? AND user_id = ? LIMIT 1");
                            $stmt->execute([$item['id'], $user_id]);
                            $res = $stmt->fetch();
                            if ($res) $target_id = $res['ingredient_id'];
                        }

                        if ($target_id > 0) {
                            // 1. 在庫（ingredient）からデータを取得
                            $stmt = $pdo->prepare("SELECT * FROM ingredient WHERE ingredient_id = ?");
                            $stmt->execute([$target_id]);
                            $data = $stmt->fetch();

                            if ($data) {
                                // 【完全版】使用量の決定ロジック
                                // フロントから送られてくるキー名が use_quantity でも quantity でも対応可能にする
                                $use_qty = 0;
                                if (!empty($item['use_quantity'])) {
                                    $use_qty = (float)$item['use_quantity'];
                                } elseif (!empty($item['quantity'])) {
                                    $use_qty = (float)$item['quantity'];
                                } else {
                                    $use_qty = (float)$data['quantity'];
                                }

                                $remain_qty = (float)$data['quantity'] - $use_qty;

                                // 2. 調理中（cooking_now）へ指定した数量で登録
                                $ins = $pdo->prepare("INSERT INTO cooking_now (user_id, food, quantity, unit, original_ingredient_id, original_storage_location_id) VALUES (?, ?, ?, ?, ?, ?)");
                                $ins->execute([$user_id, $data['food_name'], $use_qty, $data['unit'], $data['ingredient_id'], $data['storage_location_id']]);

                                // 3. 在庫（ingredient）の数量を更新（減算）
                                if ($remain_qty > 0) {
                                    $pdo->prepare("UPDATE ingredient SET quantity = ? WHERE ingredient_id = ?")
                                        ->execute([$remain_qty, $data['ingredient_id']]);
                                } else {
                                    // 0個以下になったら在庫から削除
                                    $pdo->prepare("DELETE FROM ingredient WHERE ingredient_id = ?")
                                        ->execute([$data['ingredient_id']]);
                                }
                            }
                        }
                    }
                }
                $pdo->commit();
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            
            // 最新の調理中リストを取得して返す
            $stmt = $pdo->prepare("SELECT * FROM cooking_now WHERE user_id = ?");
            $stmt->execute([$user_id]);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
            break;

     case 'return_item':
    $pdo->beginTransaction();
    try {
        // 1. 調理中のデータを取得
        $stmt = $pdo->prepare("SELECT * FROM cooking_now WHERE id = ?");
        $stmt->execute([(int)$input['cooking_id']]);
        $item = $stmt->fetch();

        if ($item) {
            // 2. 戻す場所のIDを決定
            // destinationが "original" なら元の場所IDを使用、そうでなければ指定された場所IDを使用
            $target_loc_id = ($input['destination'] === 'original') 
                             ? $item['original_storage_location_id'] 
                             : (int)$input['destination'];

            // 3. 在庫（ingredient）に数量を戻す（新規追加ではなくUPDATEを優先する場合の例）
            // ※同じ食材があれば数量を加算、なければ新規登録するロジックが理想的です
            $ins = $pdo->prepare("INSERT INTO ingredient (user_id, category_id, storage_location_id, food_name, quantity, unit) VALUES (?, 6, ?, ?, ?, ?)");
            $ins->execute([$user_id, $target_loc_id, $item['food'], $item['quantity'], $item['unit']]);

            // 4. 調理中から削除
            $pdo->prepare("DELETE FROM cooking_now WHERE id = ?")->execute([$item['id']]);
        }
        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    break;

        case 'complete':
            // 履歴への移動処理は維持
            $stmt = $pdo->prepare("SELECT * FROM cooking_now WHERE user_id = ?");
            $stmt->execute([$user_id]);
            foreach ($stmt->fetchAll() as $item) {
                $pdo->prepare("INSERT INTO cooking_history (user_id, dish_name, used_food_name, quantity, unit, cooked_at) VALUES (?, '料理', ?, ?, ?, NOW())")
                    ->execute([$user_id, $item['food'], $item['quantity'], $item['unit']]);
            }
            // 調理終了時に調理中リストから削除
            $pdo->prepare("DELETE FROM cooking_now WHERE user_id = ?")->execute([$user_id]);
            echo json_encode(['success' => true]);
            break;

        default:
            throw new Exception("無効なアクション");
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}