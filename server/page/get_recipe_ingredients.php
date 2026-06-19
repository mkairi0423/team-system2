<?php
// ==================================================================================
// 最終完全版：表記揺れ対策・API接続最適化・エラーハンドリング統合版
// ==================================================================================

set_time_limit(60); 
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../helpers/gemini_api.php';
require_once __DIR__ . '/../../helpers/utils.php';

// .env ロード
$envPath = __DIR__ . '/../../.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim(trim($value), '"\'');
        }
    }
}

$api_key = $_ENV['GEMINI_API_KEY'] ?? '';
$userId = $_GET['user_id'] ?? null;
$keyword = $_GET['keyword'] ?? '';

if (empty($api_key) || !$userId || !$keyword) {
    echo json_encode(["success" => false, "message" => "設定またはパラメータが不足しています。"]);
    exit;
}

try {
    $pdo = getPDO();

    // 1. AIリクエスト (最新モデル gemini-2.5-flash を使用)
    $model = 'gemini-2.5-flash';
    $url = "https://generativelanguage.googleapis.com/v1/models/{$model}:generateContent?key={$api_key}";

    $prompt = "「{$keyword}」に必要な主要食材を3〜5つ、ひらがなでJSON配列のみ返してください。Markdown記法や解説は一切禁止。例: [\"たまねぎ\", \"にんじん\", \"じゃがいも\"]";

    $data = [
        'contents' => [['parts' => [['text' => $prompt]]]],
        'generationConfig' => ['temperature' => 0.3]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) throw new Exception("APIエラー(コード{$httpCode}): " . $response);

    $result = json_decode($response, true);
    $aiResponseText = $result['candidates'][0]['content']['parts'][0]['text'] ?? '[]';
    
    // JSON整形（Markdown除去）
    $aiResponseText = preg_replace('/^```json\s*/', '', $aiResponseText);
    $aiResponseText = preg_replace('/```$/', '', $aiResponseText);
    $neededFoods = json_decode(trim($aiResponseText), true);

    // 2. 在庫マッチング（表記揺れに強い比較ロジック）
    $sql = "SELECT food_name FROM ingredient WHERE user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    $userStocks = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // 表記揺れ対応辞書
    $synonyms = ['たまご' => ['たまご', 'らん', 'たまごやき', 'だしまたまご', '卵']];

    $matchingResults = [];
    foreach ($neededFoods as $foodName) {
        $found = false;
        $cleanNeeded = mb_convert_kana($foodName, "cVs", "UTF-8");

        foreach ($userStocks as $stockName) {
            $cleanStock = mb_convert_kana($stockName, "cVs", "UTF-8");
            
            // 相互包含チェック
            if (mb_strpos($cleanStock, $cleanNeeded) !== false || mb_strpos($cleanNeeded, $cleanStock) !== false) {
                $found = true;
            } 
            // 辞書判定
            elseif (isset($synonyms[$cleanNeeded])) {
                foreach ($synonyms[$cleanNeeded] as $syn) {
                    if (mb_strpos($cleanStock, $syn) !== false) {
                        $found = true;
                        break;
                    }
                }
            }
            if ($found) break;
        }
        $matchingResults[] = ["food" => $foodName, "in_stock" => $found];
    }

    echo json_encode(["success" => true, "ingredients" => $matchingResults], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}