<?php
// ==================================================================================
// page/food_scan_server.php （関数化・storage_place除外・バグ完全修正版）
// ==================================================================================

// 🚀 解析時間を十分に確保するため、制限時間を「無制限」に設定
set_time_limit(0);

header('Content-Type: application/json; charset=utf-8');

// 🛠️ 1. 各種ヘルパーファイルの読み込み
require_once __DIR__ . '/../../helpers/gemini_api.php';
require_once __DIR__ . '/../../helpers/utils.php';
require_once __DIR__ . '/../AI_info/ai_rule_scan.php';

// 🛠️ 2. .envファイルの読み込み
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

// 💡 3. APIキーの確認
$api_key = $_ENV['GEMINI_API_KEY'] ?? '';

if (empty($api_key)) {
    echo json_encode(['error' => 'APIキーが設定されていません。プロジェクトルートの.envを確認してください。'], JSON_UNESCAPED_UNICODE);
    exit;
}

// フロントからのJSONデータデコード
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['error' => 'リクエストデータが空です'], JSON_UNESCAPED_UNICODE);
    exit;
}

$action = $input['action'] ?? 'scan';

// ==================================================================================
// 🔥 処理パターンA：レシート画像をAIでスキャンする（関数化ですっきり）
// ==================================================================================
if ($action === 'scan') {
    if (!isset($input['image'])) {
        echo json_encode(['error' => '画像データが送信されていません'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $base64_image = preg_replace('#^data:image/\w+;base64,#i', '', $input['image']);

    // 💡 下部で定義した独自関数を呼び出して解析を実行
    $result = scan_rule($base64_image, $api_key);

    if ($result === false) {
        echo json_encode(['error' => 'AIの応答を正しく解析できませんでした。時間をおいて試すか、ログを確認してください。'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // クライアント（food_scan.js）に解析データを返却
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit;
}

// ==================================================================================
// 🔥 処理パターンB：ユーザーが厳選・選択した食材データをDBに保存する（既存DB互換版）
// ==================================================================================
if ($action === 'save') {
    $items = $input['items'] ?? [];
    if (empty($items)) {
        echo json_encode(['error' => '保存するデータがありません'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    try {
        $pdo = getPDO();

        // 💡 既存のDB構造に合わせて storage_place は含めないインサート文
        $stmt = $pdo->prepare("
            INSERT INTO ingredients (user_id, category_id, food_name, quantity, expiration_date, term_type)
            VALUES (:user_id, :category_id, :_, :quantity, :expiration_date, :term_type)
        ");

        $current_user_id = 1;

        foreach ($items as $item) {
            $categoryId = 4; // その他
            $name = $item['food_name'];

            if (preg_match('/(豚|鶏|牛|肉|ハンバーグ|ひき肉|ウインナー|ハム)/u', $name)) {
                $categoryId = 1;
            } elseif (preg_match('/(キャベツ|玉ねぎ|たまねぎ|大根|レタス|野菜|トマト|人参|きゅうり|ピーマン|白菜)/u', $name)) {
                $categoryId = 2;
            } elseif (preg_match('/(サーモン|鮭|サバ|魚|エビ|イカ|卵|たまご|豆腐|納豆|牛乳|チーズ)/u', $name)) {
                $categoryId = 3;
            }

            // フロントのカレンダーから編集・確定された日付を使用
            $expirationDate = !empty($item['custom_expiry_date']) ? $item['custom_expiry_date'] : date('Y-m-d');

            $termTypeRaw = $item['term_type'] ?? 'best_before';
            $termTypeJapanese = ($termTypeRaw === 'use_by') ? '消費期限' : '賞味期限';

            $stmt->execute([
                ':user_id'         => $current_user_id,
                ':category_id'     => $categoryId,
                ':_'            => $name,
                ':quantity'        => (int)$item['estimated_weight'],
                ':expiration_date' => $expirationDate,
                ':term_type'       => $termTypeJapanese
            ]);
        }

        echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['error' => 'データベース一括登録中にエラーが発生しました: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
