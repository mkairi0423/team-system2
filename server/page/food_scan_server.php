<?php
// ==================================================================================
// page/food_scan_server.php （コントローラー側・リファクタリング完了版）
// ==================================================================================

set_time_limit(0);
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../helpers/gemini_api.php';
require_once __DIR__ . '/../../helpers/utils.php';
require_once __DIR__ . '/../AI_info/ai_rule_scan.php';
// 🟢 新しく作成したDB関数ファイルを読み込む
require_once __DIR__ . '/../DB_function/food_scan_db.php';

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
        // 現在ログインしているユーザーのID（セッションがなければデフォルト 1）
        $current_user_id = $_SESSION['user']['uid'] ?? 1;

        // 🟢 データベースへの一括登録処理を関数呼び出しの1行に圧縮！
        scan_register($current_user_id, $items);

        echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
        exit;
    } catch (PDOException $e) {
        // 関数内で発生した例外をここで安全にキャッチしてエラー返却
        echo json_encode(['error' => 'データベース一括登録中にエラーが発生しました: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
