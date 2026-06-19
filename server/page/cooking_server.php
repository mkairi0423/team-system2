<?php
// ヘッダーの設定（JSON返却）
header('Content-Type: application/json; charset=utf-8');

// データベース接続設定
try {
    $dsn = 'mysql:host=localhost;dbname=team_system2;charset=utf8mb4';
    $username = 'root';
    $password = 'root';
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'DB接続エラー: ' . $e->getMessage()]);
    exit;
}

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
            $items = $input['items'] ?? []; // [{id: 1, quantity: 2}, ...]

            if (!$user_id || empty($items)) {
                throw new Exception('ユーザーIDまたは食材リストが不足しています。');
            }

            $pdo->beginTransaction();

            // 新しく調理を始める前に、現在のユーザーの古い調理中データを一括削除してリセットする
            $stmtClear = $pdo->prepare("DELETE FROM cooking_now WHERE user_id = ?");
            $stmtClear->execute([$user_id]);

            foreach ($items as $item) {
                $ingredient_id = $item['id'];
                $use_quantity = $item['quantity'];

                // 🌟 【修正】単数形の `ingredient` テーブルと `ingredient_id` カラムに変更
                $stmt = $pdo->prepare("SELECT * FROM ingredient WHERE ingredient_id = ? AND user_id = ?");
                $stmt->execute([$ingredient_id, $user_id]);
                $ing = $stmt->fetch();

                if (!$ing) {
                    continue; // 該当する食材データがなければスキップ
                }

                // cooking_now テーブルへデータを挿入
                $stmtInsert = $pdo->prepare("
                    INSERT INTO cooking_now (user_id, original_ingredient_id, food, quantity, unit, original_storage_location_id, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmtInsert->execute([
                    $user_id,
                    $ing['ingredient_id'], // 🌟 修正
                    $ing['food_name'],
                    $use_quantity,
                    $ing['unit'],
                    $ing['storage_location_id']
                ]);

                // ⚠️ CASCADE削除対策：ここではDELETEせず数量を0にする
                // 🌟 【修正】単数形の `ingredient` テーブルと `ingredient_id` カラムに変更
                $stmtUpdate = $pdo->prepare("UPDATE ingredient SET quantity = 0 WHERE ingredient_id = ?");
                $stmtUpdate->execute([$ingredient_id]);
            }

            // 調理中リストを取得してフロントへ返却（cooking_nowの主キーid、food、quantity、unitを取得）
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

            // 🌟 【修正】単数形の `ingredient` テーブルと `ingredient_id` カラムに復活させる
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

            // 1. 現在調理中の食材リスト（cooking_now）をすべて取得
            $stmtGetNow = $pdo->prepare("SELECT food, quantity, unit FROM cooking_now WHERE user_id = ?");
            $stmtGetNow->execute([$user_id]);
            $cooking_items = $stmtGetNow->fetchAll();

            // 2. 履歴テーブル（cooking_history）へ保存
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

            // 3. 🌟 【修正】単数形の `ingredient` テーブルのキープデータを完全削除
            $stmtDelIngredients = $pdo->prepare("
                DELETE FROM ingredient 
                WHERE ingredient_id IN (SELECT original_ingredient_id FROM cooking_now WHERE user_id = ?)
            ");
            $stmtDelIngredients->execute([$user_id]);

            // 4. cooking_now テーブルからも一括削除
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
                // 🌟 【修正】単数形の `ingredient` テーブルと `ingredient_id` カラムに復元
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

        // --------------------------------------------------------
        // ⑤ 料理履歴の取得（action: get_history） 【★ここを追加！】
        // --------------------------------------------------------
        case 'get_history':
            // JSから送られてきた user_id を取得（なければデフォルトで 1）
            $user_id = $_GET['user_id'] ?? 1;

            // 料理履歴を cooked_at が新しい順（最新順）で取得するSQL
            $stmtHistory = $pdo->prepare("
                SELECT id, dish_name, used_food_name, quantity, unit, cooked_at 
                FROM cooking_history 
                WHERE user_id = ? 
                ORDER BY cooked_at DESC
            ");
            $stmtHistory->execute([$user_id]);
            $history_list = $stmtHistory->fetchAll();

            $response = [
                'success' => true,
                'message' => '履歴データを取得しました。',
                'data' => $history_list
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