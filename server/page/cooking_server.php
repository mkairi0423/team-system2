<?php
// ヘッダーの設定（JSON返却）
header('Content-Type: application/json; charset=utf-8');

// 🟢 すでに定義されている getPDO() を使うために utils.php だけを読み込む
// (def.php は utils.php の中で自動的に require_once されるため、ここでは不要です)
require_once __DIR__ . "/../helpers/utils.php";

// 🟢 もともと作ってある共通関数をそのまま呼び出し
$pdo = getPDO();

$action = $_GET['action'] ?? '';
$raw_input = file_get_contents('php://input');
$input = json_decode($raw_input, true) ?? [];

$response = ['success' => false, 'message' => '無効なアクションです。'];

try {
    switch ($action) {
        // --------------------------------------------------------
        // ① 調理開始（action: start）
        // --------------------------------------------------------
        case 'start':
            $user_id = $input['user_id'] ?? null;
            $items = $input['items'] ?? [];

            if (!$user_id || empty($items)) {
                throw new Exception('ユーザーIDまたは食材リストが不足しています。');
            }

            $pdo->beginTransaction();

            $stmtClear = $pdo->prepare("DELETE FROM cooking_now WHERE user_id = ?");
            $stmtClear->execute([$user_id]);

            foreach ($items as $item) {
                $ingredient_id = $item['id'];
                $use_quantity = $item['quantity'];

                // 🟢 テーブル名：ingredient / 主キー：ingredient_id
                $stmt = $pdo->prepare("SELECT * FROM ingredient WHERE ingredient_id = ? AND user_id = ?");
                $stmt->execute([$ingredient_id, $user_id]);
                $ing = $stmt->fetch();

                if (!$ing) {
                    continue;
                }

                $stmtInsert = $pdo->prepare("
                    INSERT INTO cooking_now (user_id, original_ingredient_id, food, quantity, unit, original_storage_location_id, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmtInsert->execute([
                    $user_id,
                    $ing['ingredient_id'],
                    $ing['food_name'],
                    $use_quantity,
                    $ing['unit'],
                    $ing['storage_location_id']
                ]);

                // 🟢 対象テーブル：ingredient / 条件：ingredient_id
                $stmtUpdate = $pdo->prepare("UPDATE ingredient SET quantity = 0 WHERE ingredient_id = ?");
                $stmtUpdate->execute([$ingredient_id]);
            }

            $stmtList = $pdo->prepare("SELECT id, food, quantity, unit FROM cooking_now WHERE user_id = ?");
            $stmtList->execute([$user_id]);
            $cooking_list = $stmtList->fetchAll();

            $pdo->commit();

            $response = [
                'success' => true,
                'message' => '調理を開始しました。対象食材をキープしました。',
                'data' => $cooking_list
            ];
            break;

        // --------------------------------------------------------
        // ② 食材の個別差し戻し（action: return_item）
        // --------------------------------------------------------
        case 'return_item':
            $cooking_id = $input['cooking_id'] ?? null;
            $destination = $input['destination'] ?? 'original';

            if (!$cooking_id) {
                throw new Exception('調理中データIDが指定されていません。');
            }

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT * FROM cooking_now WHERE id = ?");
            $stmt->execute([$cooking_id]);
            $cooking_item = $stmt->fetch();

            if (!$cooking_item) {
                throw new Exception('該当の調理中データが見つかりません。');
            }

            $target_location_id = $cooking_item['original_storage_location_id'];
            if (is_numeric($destination)) {
                $target_location_id = intval($destination);
            }

            // 🟢 対象テーブル：ingredient / 条件：ingredient_id
            $stmtRestore = $pdo->prepare("
                UPDATE ingredient 
                SET quantity = ?, storage_location_id = ? 
                WHERE ingredient_id = ?
            ");
            $stmtRestore->execute([
                $cooking_item['quantity'],
                $target_location_id,
                $cooking_item['original_ingredient_id']
            ]);

            $stmtDelete = $pdo->prepare("DELETE FROM cooking_now WHERE id = ?");
            $stmtDelete->execute([$cooking_id]);

            $pdo->commit();

            $response = ['success' => true, 'message' => '食材を在庫に戻しました。'];
            break;

        // --------------------------------------------------------
        // ③ 料理完了（action: complete）
        // --------------------------------------------------------
        case 'complete':
            $user_id = $input['user_id'] ?? null;
            $dish_name = $input['dish_name'] ?? 'AI考案料理';

            if (!$user_id) {
                throw new Exception('ユーザーIDが指定されていません。');
            }

            $pdo->beginTransaction();

            $stmtGetNow = $pdo->prepare("
                SELECT food, quantity, unit 
                FROM cooking_now 
                WHERE user_id = ?
            ");
            $stmtGetNow->execute([$user_id]);
            $cooking_items = $stmtGetNow->fetchAll();

            if (!empty($cooking_items)) {
                $stmtInsertHistory = $pdo->prepare("
                    INSERT INTO cooking_history (user_id, dish_name, used_food_name, quantity, unit, cooked_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");

                foreach ($cooking_items as $item) {
                    $stmtInsertHistory->execute([
                        $user_id,
                        $dish_name,
                        $item['food'],
                        $item['quantity'],
                        $item['unit']
                    ]);
                }
            }

            // 🟢 対象テーブル：ingredient / 条件：ingredient_id
            $stmtDelIngredients = $pdo->prepare("
                DELETE FROM ingredient 
                WHERE ingredient_id IN (SELECT original_ingredient_id FROM cooking_now WHERE user_id = ?)
            ");
            $stmtDelIngredients->execute([$user_id]);

            $stmtDelCooking = $pdo->prepare("DELETE FROM cooking_now WHERE user_id = ?");
            $stmtDelCooking->execute([$user_id]);

            $pdo->commit();

            $response = [
                'success' => true,
                'message' => "「{$dish_name}」の調理記録を保存し、在庫を正式に消費しました！"
            ];
            break;

        // --------------------------------------------------------
        // ④ 24時間放置データの自動復元（action: auto_restore）
        // --------------------------------------------------------
        case 'auto_restore':
            $pdo->beginTransaction();

            $stmt = $pdo->query("SELECT * FROM cooking_now WHERE created_at < NOW() - INTERVAL 24 HOUR");
            $expired_items = $stmt->fetchAll();

            $restored_count = 0;

            foreach ($expired_items as $item) {
                // 🟢 対象テーブル：ingredient / 条件：ingredient_id
                $stmtRestore = $pdo->prepare("
                    UPDATE ingredient 
                    SET quantity = ?, storage_location_id = ? 
                    WHERE ingredient_id = ?
                ");
                $stmtRestore->execute([
                    $item['quantity'],
                    $item['original_storage_location_id'],
                    $item['original_ingredient_id']
                ]);

                $stmtDelete = $pdo->prepare("DELETE FROM cooking_now WHERE id = ?");
                $stmtDelete->execute([$item['id']]);

                $restored_count++;
            }

            $pdo->commit();

            $response = [
                'success' => true,
                'message' => "24時間経過したデータを自動復元しました。処理件数: {$restored_count}件"
            ];
            break;
    }
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $response = [
        'success' => false,
        'message' => 'エラーが発生しました: ' . $e->getMessage()
    ];
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
