<?php
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
    echo json_encode(["success" => false, "message" => "パラメータ不足"]);
    exit;
}

try {
    $pdo = getPDO();

    // AIリクエスト
    $model = 'gemini-2.5-flash';
    $url = "https://generativelanguage.googleapis.com/v1/models/{$model}:generateContent?key={$api_key}";
    $prompt = "「{$keyword}」に必要な主要食材を3〜5つ、ひらがなでJSON配列のみ返してください。Markdownや解説は不要。例: [\"たまねぎ\", \"にんじん\", \"じゃがいも\"]";

    $data = ['contents' => [['parts' => [['text' => $prompt]]]], 'generationConfig' => ['temperature' => 0.3]];
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);
    $aiResponseText = preg_replace('/^```json\s*|```$/', '', $result['candidates'][0]['content']['parts'][0]['text'] ?? '[]');
    $neededFoods = json_decode(trim($aiResponseText), true) ?? [];

    // ★SQLを修正：テーブル名は ingredient、IDは ingredient_id
    $sql = "SELECT ingredient_id, food_name, quantity, unit FROM ingredient WHERE user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    $userStocks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $matchingResults = [];
    foreach ($neededFoods as $foodName) {
        $found = false;
        $stockData = ["id" => null, "quantity" => 0, "unit" => ""];
        
        foreach ($userStocks as $stock) {
            // 文字列マッチング
            if (mb_strpos($stock['food_name'], $foodName) !== false || mb_strpos($foodName, $stock['food_name']) !== false) {
                $found = true;
                $stockData = [
                    "id" => $stock['ingredient_id'],
                    "quantity" => $stock['quantity'],
                    "unit" => $stock['unit']
                ];
                break;
            }
        }
        $matchingResults[] = [
            "id" => $stockData["id"],
            "food_name" => $foodName,
            "in_stock" => $found,
            "quantity" => (float)$stockData["quantity"],
            "unit" => $stockData["unit"]
        ];
    }
    echo json_encode(["success" => true, "ingredients" => $matchingResults], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}