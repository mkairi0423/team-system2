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
$user_id = isset($input['user_id']) ? (int)$input['user_id'] : null;

try {
    switch ($action) {
        case 'get_list':
            $stmt = $pdo->prepare("SELECT * FROM cooking_now WHERE user_id = ?");
            $stmt->execute([$user_id]);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
            break;

        case 'update_qty':
            $stmt = $pdo->prepare("UPDATE cooking_now SET quantity = ? WHERE id = ?");
            $stmt->execute([(float)$input['quantity'], (int)$input['cooking_id']]);
            echo json_encode(['success' => true]);
            break;

        case 'return_item':
            $stmt = $pdo->prepare("SELECT * FROM cooking_now WHERE id = ?");
            $stmt->execute([(int)$input['cooking_id']]);
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
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}