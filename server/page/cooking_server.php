<?php
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
        case 'get_list':
            $stmt = $pdo->prepare("SELECT * FROM cooking_now WHERE user_id = ?");
            $stmt->execute([$user_id]);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
            break;

        case 'start':
            $pdo->beginTransaction();
            try {
                if (!empty($input['items'])) {
                    foreach ($input['items'] as $item) {
                        // 1. 在庫を探す（IDまたは名前で検索）
                        $stmt = $pdo->prepare("SELECT * FROM ingredient WHERE (ingredient_id = ? OR food_name = ?) AND user_id = ? LIMIT 1");
                        $stmt->execute([$item['id'] ?? 0, $item['id'] ?? '', $user_id]);
                        $data = $stmt->fetch();

                        if ($data) {
                            // 在庫がある場合は計算して登録
                            $use_qty = (float)($item['use_quantity'] ?? $item['quantity'] ?? $data['quantity']);
                            $ins = $pdo->prepare("INSERT INTO cooking_now (user_id, food, quantity, unit, original_ingredient_id, original_storage_location_id) VALUES (?, ?, ?, ?, ?, ?)");
                            $ins->execute([$user_id, $data['food_name'], $use_qty, $data['unit'], $data['ingredient_id'], $data['storage_location_id']]);
                            
                            $remain_qty = (float)$data['quantity'] - $use_qty;
                            if ($remain_qty > 0.001) {
                                $pdo->prepare("UPDATE ingredient SET quantity = ? WHERE ingredient_id = ?")->execute([$remain_qty, $data['ingredient_id']]);
                            } else {
                                $pdo->prepare("DELETE FROM ingredient WHERE ingredient_id = ?")->execute([$data['ingredient_id']]);
                            }
                        } else {
                            // ★在庫が見つからない場合、送られてきたデータを「そのまま」登録する
                            $ins = $pdo->prepare("INSERT INTO cooking_now (user_id, food, quantity, unit) VALUES (?, ?, ?, ?)");
                            $ins->execute([$user_id, $item['food'] ?? '不明', $item['use_quantity'] ?? 0, $item['unit'] ?? '個']);
                        }
                    }
                }
                $pdo->commit();
                
                $stmt = $pdo->prepare("SELECT * FROM cooking_now WHERE user_id = ?");
                $stmt->execute([$user_id]);
                echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
            } catch (Exception $e) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            break;

        case 'return_item':
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("SELECT * FROM cooking_now WHERE id = ?");
                $stmt->execute([(int)$input['cooking_id']]);
                $item = $stmt->fetch();
                if ($item) {
                    $target_loc_id = ($input['destination'] === 'original') ? $item['original_storage_location_id'] : (int)$input['destination'];
                    $stmt = $pdo->prepare("SELECT ingredient_id FROM ingredient WHERE user_id = ? AND food_name = ? AND storage_location_id = ? LIMIT 1");
                    $stmt->execute([$user_id, $item['food'], $target_loc_id]);
                    $existing = $stmt->fetch();

                    if ($existing) {
                        $pdo->prepare("UPDATE ingredient SET quantity = quantity + ? WHERE ingredient_id = ?")->execute([(float)$item['quantity'], $existing['ingredient_id']]);
                    } else {
                        $pdo->prepare("INSERT INTO ingredient (user_id, category_id, storage_location_id, food_name, quantity, unit) VALUES (?, 6, ?, ?, ?, ?)")
                            ->execute([$user_id, $target_loc_id, $item['food'], (float)$item['quantity'], $item['unit']]);
                    }
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
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("SELECT * FROM cooking_now WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $items = $stmt->fetchAll();
                if (!empty($items)) {
                    $ins_hist = $pdo->prepare("INSERT INTO cooking_history (user_id, dish_name) VALUES (?, '料理')");
                    $ins_hist->execute([$user_id]);
                    $history_id = $pdo->lastInsertId();
                    foreach ($items as $item) {
                        $pdo->prepare("INSERT INTO cooking_history_ingredient (history_id, category_id, used_food_name, quantity, unit) VALUES (?, 1, ?, ?, ?)")
                            ->execute([$history_id, $item['food'], (float)$item['quantity'], $item['unit']]);
                    }
                }
                $pdo->prepare("DELETE FROM cooking_now WHERE user_id = ?")->execute([$user_id]);
                $pdo->commit();
                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}