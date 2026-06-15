<?php
// ==================================================================================
// page/food_scan_server.php （1列シンプルデータ受取・最新DB構造 適合版）
// ==================================================================================

set_time_limit(0);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../helpers/gemini_api.php';
require_once __DIR__ . '/../../helpers/utils.php';
require_once __DIR__ . '/../AI_info/ai_rule_scan.php';

$envPath = __DIR__ . '/../../.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $clean_key = trim($key);
            $clean_value = trim(trim($value), '"\'');
            $_ENV[$clean_key] = $clean_value;
        }
    }
}

$api_key = $_ENV['GEMINI_API_KEY'] ?? '';
if (empty($api_key)) {
    echo json_encode(['error' => 'APIキーが設定されていません。'], JSON_UNESCAPED_UNICODE);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['error' => 'リクエストデータが空です'], JSON_UNESCAPED_UNICODE);
    exit;
}

$action = $input['action'] ?? 'scan';

// 処理パターンA：スキャン
if ($action === 'scan') {
    if (!isset($input['image'])) {
        echo json_encode(['error' => '画像データが送信されていません'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $base64_image = preg_replace('#^data:image/\w+;base64,#i', '', $input['image']);
    $result = scan_rule($base64_image, $api_key);
    if ($result === false) {
        echo json_encode(['error' => 'AIの応答を正しく解析できませんでした。'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit;
}

// 処理パターンB：保存
if ($action === 'save') {
    $items = $input['items'] ?? [];
    if (empty($items)) {
        echo json_encode(['error' => '保存するデータがありません'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    try {
        $pdo = getPDO();

        $stmt = $pdo->prepare("
            INSERT INTO ingredients (user_id, category_id, storage_location_id, food_name, quantity, unit, expiration_date, term_type)
            VALUES (:user_id, :category_id, :storage_location_id, :food_name, :quantity, :unit, :expiration_date, :term_type)
        ");

        $current_user_id = 1;

        foreach ($items as $item) {
            $name = $item['food_name'];

            // ① カテゴリID判定
            $categoryId = 4;
            if (preg_match('/(豚|鶏|牛|肉|ハンバーグ|ひき肉|ウインナー|ハム)/u', $name)) {
                $categoryId = 1;
            } elseif (preg_match('/(キャベツ|玉ねぎ|たまねぎ|大根|レタス|野菜|トマト|人参|きゅうり|ピーマン|白菜)/u', $name)) {
                $categoryId = 2;
            } elseif (preg_match('/(サーモン|鮭|サバ|魚|エビ|イカ|卵|たまご|豆腐|納豆|牛乳|チーズ)/u', $name)) {
                $categoryId = 3;
            }

            // ② 保管場所文字列 ➔ マスタID
            $storageText = $item['storage_place'] ?? '冷蔵庫';
            $storageLocationId = 1;
            if (strpos($storageText, '冷凍庫') !== false) {
                $storageLocationId = 2;
            } elseif (strpos($storageText, '常温') !== false || strpos($storageText, 'パントリー') !== false) {
                $storageLocationId = 3;
            } elseif (strpos($storageText, '野菜室') !== false) {
                $storageLocationId = 4;
            }

            // ③ 期限日
            $expirationDate = !empty($item['custom_expiry_date']) ? $item['custom_expiry_date'] : date('Y-m-d');

            // ④ 期限の種類
            $termTypeRaw = $item['term_type'] ?? 'best_before';
            $termTypeJapanese = ($termTypeRaw === 'use_by') ? '消費期限' : '賞味期限';

            // ⑤ 画面から受け取った値を丸めてDBに保存
            $rawQuantity = isset($item['quantity']) ? (float)$item['quantity'] : 1.0;
            $quantityValue = ($rawQuantity > 0 && $rawQuantity < 1) ? 1 : (int)round($rawQuantity);

            $unitValue = !empty($item['unit']) ? $item['unit'] : '個';

            // ENUM安全ガード
            $validUnits = ['g', '個', '本', '玉', 'パック', '枚', 'ml'];
            if (!in_array($unitValue, $validUnits, true)) {
                $unitValue = '個';
            }

            $stmt->execute([
                ':user_id'             => $current_user_id,
                ':category_id'         => $categoryId,
                ':storage_location_id' => $storageLocationId,
                ':food_name'           => $name,
                ':quantity'            => $quantityValue,
                ':unit'                => $unitValue,
                ':expiration_date'     => $expirationDate,
                ':term_type'           => $termTypeJapanese
            ]);
        }

        echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['error' => 'データベース一括登録中にエラーが発生しました: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
