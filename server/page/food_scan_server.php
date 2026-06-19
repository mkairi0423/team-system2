<?php
// ==================================================================================
// page/food_scan_server.php （通信エラー完全可視化・デバッグ強化版）
// ==================================================================================

set_time_limit(0);
// 💡 エラーが発生しても強制的にJSONとしてブラウザに返すよう、エラー表示を一時制御
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../helpers/gemini_api.php';
require_once __DIR__ . '/../../helpers/utils.php';
require_once __DIR__ . '/../AI_info/ai_rule_scan.php';
require_once __DIR__ . '/../DB_function/scan_DB.php';

// シャットダウン時（Fatal Error発生時）に無理やりJSONでエラーを吐き出す仕掛け
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE || $error['type'] === E_CORE_ERROR || $error['type'] === E_COMPILE_ERROR)) {
        echo json_encode([
            'error' => 'PHPのシステムエラー（Fatal Error）が発生しました。',
            'debug_php_error' => $error
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
});

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
    echo json_encode(['error' => 'APIキーが設定されていません。.envファイルを確認してください。'], JSON_UNESCAPED_UNICODE);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['error' => 'リクエストデータが空です（JSONパース失敗）'], JSON_UNESCAPED_UNICODE);
    exit;
}

$action = $input['action'] ?? 'scan';

// ==================================================================================
// 処理パターンA：スキャン（原因特定・クレンジング強化）
// ==================================================================================
if ($action === 'scan') {
    if (!isset($input['image'])) {
        echo json_encode(['error' => '画像データが送信されていません'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $base64_image = preg_replace('#^data:image/\w+;base64,#i', '', $input['image']);

    // 💡 バックバッファを監視して、`scan_rule` 内部での予期せぬ出力を横取りする準備
    ob_start();
    $raw_result = scan_rule($base64_image, $api_key);
    $unexpected_output = ob_get_clean();

    // ❌ AIの応答が取得できなかった場合
    if ($raw_result === false || empty($raw_result)) {
        echo json_encode([
            'error' => 'AIの応答が空か、通信に失敗しました。',
            'debug_info' => [
                'api_key_status' => (empty($api_key) ? '未設定' : '設定あり(' . strlen($api_key) . '文字)'),
                'image_size' => strlen($base64_image) . ' バイト',
                'php_unexpected_output' => $unexpected_output ? $unexpected_output : 'なし',
                'possible_reasons' => [
                    '1. データベースや外部API（Gemini）への接続がタイムアウトしている',
                    '2. Windowsローカル環境（XAMPP等）のcURLがSSL証明書エラーを起こしている（curl_setopt の SSL_VERIFYPEER 設定が必要）',
                    '3. APIキーが失効している、または制限に達している'
                ]
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 💡 配列型で返ってきた場合はそのまま通す
    if (is_array($raw_result)) {
        echo json_encode($raw_result, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 💡 文字列型で返ってきた場合は、不純物を徹底的に掃除する
    if (is_string($raw_result)) {
        $clean_json = trim($raw_result);

        // マークダウンの装飾（```json ... ```）をすべて剥ぎ取る
        $clean_json = preg_replace('/^```json\s*/i', '', $clean_json);
        $clean_json = preg_replace('/^```\s*/', '', $clean_json);
        $clean_json = preg_replace('/```$/', '', $clean_json);
        $clean_json = trim($clean_json);

        $result = json_decode($clean_json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            echo json_encode([
                'error' => 'AIの応答JSONパース失敗: ' . json_last_error_msg(),
                'debug_raw' => mb_substr($clean_json, 0, 300) . '...'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode(['error' => '予測不能なデータ型が返されました。'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ==================================================================================
// 処理パターンB：保存
// ==================================================================================
if ($action === 'save') {
    $items = $input['items'] ?? [];
    if (empty($items)) {
        echo json_encode(['error' => '保存するデータがありません'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    try {
        $current_user_id = $_SESSION['user']['uid'] ?? 1;
        scan_register($current_user_id, $items);
        echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['error' => 'データベース一括登録中にエラーが発生しました: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
