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

// 🌟 【修正】どんな形式で送られてきても確実に $user_id を取得し、なければ 1 にする安全装置
$user_id = null;
if (isset($_GET['user_id']))  $user_id = (int)$_GET['user_id'];
if (isset($_GET['userId']))   $user_id = (int)$_GET['userId'];
if (isset($input['user_id'])) $user_id = (int)$input['user_id'];
if (isset($input['userId']))  $user_id = (int)$input['userId'];

if (!$user_id) {
    $user_id = 1; // デフォルト値
}

try {
    switch ($action) {
        case 'get_history':
            if (!$user_id) {
                echo json_encode(['success' => false, 'message' => 'ユーザーIDが指定されていません。']);
                break;
            }
            // 料理履歴を cooked_at が新しい順（最新順）で取得するSQL
            $stmtHistory = $pdo->prepare("
                SELECT id, dish_name, used_food_name, quantity, unit, cooked_at 
                FROM cooking_history 
                WHERE user_id = ? 
                ORDER BY cooked_at DESC
            ");
            $stmtHistory->execute([$user_id]);
            $history_list = $stmtHistory->fetchAll();

            echo json_encode([
                'success' => true,
                'message' => '履歴データを取得しました。',
                'data' => $history_list
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'get_list':
            $stmt = $pdo->prepare("SELECT * FROM cooking_now WHERE user_id = ?");
            $stmt->execute([$user_id]);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
            break;

        case 'update_qty':
            $stmt = $pdo->prepare("UPDATE cooking_now SET quantity = ? WHERE id = ?");
            $stmt->execute([(float) $input['quantity'], (int) $input['cooking_id']]);
            echo json_encode(['success' => true]);
            break;

        case 'return_item':
            $stmt = $pdo->prepare("SELECT * FROM cooking_now WHERE id = ?");
            $stmt->execute([(int) $input['cooking_id']]);
            $item = $stmt->fetch();
            if ($item) {
                $pdo->prepare("UPDATE ingredient SET quantity = quantity + ? WHERE ingredient_id = ?")
                    ->execute([$item['quantity'], $item['original_ingredient_id']]);
                $pdo->prepare("DELETE FROM cooking_now WHERE id = ?")->execute([$item['id']]);
            }
            echo json_encode(['success' => true]);
            break;

        case 'complete':
            $stmt = $pdo->prepare("SELECT * FROM cooking_now WHERE user_id = ?");
            $stmt->execute([$user_id]);
            foreach ($stmt->fetchAll() as $item) {
                $pdo->prepare("INSERT INTO cooking_history (user_id, dish_name, used_food_name, quantity, unit, cooked_at) VALUES (?, '料理', ?, ?, ?, NOW())")
                    ->execute([$user_id, $item['food'], $item['quantity'], $item['unit']]);
            }
            $pdo->prepare("DELETE FROM cooking_now WHERE user_id = ?")->execute([$user_id]);
            echo json_encode(['success' => true]);
            break;

        default:
            // どのアクションにも当てはまらなかった場合
            echo json_encode(['success' => false, 'message' => '無効なアクションです。']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}