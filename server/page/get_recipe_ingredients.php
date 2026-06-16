<?php
// ==================================================================================
// page/get_recipe_ingredients.php （food_scan_server.php APIキー接続方式 適合版）
// ==================================================================================

set_time_limit(30); // AI通信待ちでタイムアウトしないためのセーフティ
header('Content-Type: application/json; charset=utf-8');

// 💡 チームの共通関数ファイルを読み込む
require_once __DIR__ . '/../../helpers/gemini_api.php';
require_once __DIR__ . '/../../helpers/utils.php';

// --------------------------------------------------------
// 🛠️ 動くページと同じ方法で .env から環境変数を確実にロード
// --------------------------------------------------------
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

// 💡 動くページと同じ方法で APIキーを判定・抽出
$api_key = $_ENV['GEMINI_API_KEY'] ?? '';
if (empty($api_key)) {
    echo json_encode(['error' => 'APIキーが設定されていません。'], JSON_UNESCAPED_UNICODE);
    exit;
}

// クエリパラメータからユーザーIDと検索キーワードを取得（GET通信）
$userId = $_GET['user_id'] ?? null;
$keyword = $_GET['keyword'] ?? '';

if (!$userId || !$keyword) {
    echo json_encode(["success" => false, "message" => "必要なパラメータが不足しています。"]);
    exit;
}

try {
    // 共通関数からPDOオブジェクトを取得
    $pdo = getPDO();

    // --------------------------------------------------------
    // 1. AIリクエストの構築
    // --------------------------------------------------------
    $model = 'gemini-1.5-flash'; // 混雑・制限回避のため安定版を指定
    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$api_key}";

    // プロンプトの構築
    $prompt = "「{$keyword}」を作るために一般的に必要な主要食材（調味料を除く、3〜5種類程度）のリストを教えてください。
    データベース照合を行うため、食材名は必ず漢字やカタカナ、英語を一切使わず、一般的な『ひらがな』に翻訳して出力してください。
    
    回答は余計な解説を一切含めず、以下のJSONフォーマットのみを返してください。
    [\"食材名1\", \"食材名2\", \"食材名3\"]
    
    例（カレーの場合）：[\"じゃがいも\", \"にんじん\", \"ぎゅうにく\", \"かれーるー\"]";

    // v1beta用の構造体 ＋ 純粋JSON出力設定
    $data = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ],
        'generationConfig' => [
            'responseMimeType' => 'application/json'
        ]
    ];

    // --------------------------------------------------------
    // 2. cURLによるAI通信（IPv4固定設定付き）
    // --------------------------------------------------------
    $ch = curl_init($url);
    if ($ch === false) {
        throw new Exception('cURLの初期化に失敗しました。');
    }

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

    $response = curl_exec($ch);

    if ($response === false) {
        throw new Exception(curl_error($ch));
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception("APIエラーが発生しました（コード: {$httpCode}）");
    }

    // AIのレスポンスから純粋なJSONテキスト（食材配列）を抽出
    $result = json_decode($response, true);
    $aiResponseText = $result['candidates'][0]['content']['parts'][0]['text'] ?? '[]';

    $neededFoods = json_decode(trim($aiResponseText), true);

    if (!is_array($neededFoods) || empty($neededFoods)) {
        throw new Exception("AIから料理の必要食材データを正常に解析できませんでした。");
    }

    // --------------------------------------------------------
    // 3. 現在のユーザーのすべての在庫をマスタ連携を含めて全件取得
    // --------------------------------------------------------
    $sql = "SELECT i.id, i.food_name, l.location_name 
            FROM ingredients i
            LEFT JOIN storage_locations l ON i.storage_location_id = l.id
            WHERE i.user_id = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    $userStocks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 比較用に、ユーザーの在庫名もお掃除（ひらがな化）してリスト化
    $cleanedStocks = [];
    foreach ($userStocks as $stock) {
        $cleanName = mb_convert_kana(trim($stock['food_name']), "cVs", "UTF-8");

        // フロント（JS）の判定仕様（fridge/freezer）に合わせて変換
        $locStr = 'fridge';
        if (isset($stock['location_name']) && (strpos($stock['location_name'], '冷凍') !== false)) {
            $locStr = 'freezer';
        }

        $cleanedStocks[] = [
            "id" => $stock['id'],
            "clean_name" => $cleanName,
            "location" => $locStr
        ];
    }

    // --------------------------------------------------------
    // 4. AIの必要食材とユーザー在庫をマッチング
    // --------------------------------------------------------
    $matchingResults = [];

    foreach ($neededFoods as $neededFood) {
        $inStock = false;
        $stockId = null;
        $location = null;
        $cleanNeededFood = mb_convert_kana(trim($neededFood), "cVs", "UTF-8");

        // 部分一致による検知
        foreach ($cleanedStocks as $stock) {
            if (strpos($stock['clean_name'], $cleanNeededFood) !== false || strpos($cleanNeededFood, $stock['clean_name']) !== false) {
                $inStock = true;
                $stockId = $stock['id'];
                $location = $stock['location'];
                break;
            }
        }

        $matchingResults[] = [
            "id" => $stockId,
            "food" => $neededFood,
            "in_stock" => $inStock,
            "location" => $location
        ];
    }

    // --------------------------------------------------------
    // 5. モーダル側が要求する形式に「全在庫」を整形
    // --------------------------------------------------------
    $formattedAllStocks = [];
    foreach ($userStocks as $stock) {
        $isFreezer = (isset($stock['location_name']) && strpos($stock['location_name'], '冷凍') !== false);
        $formattedAllStocks[] = [
            "id" => $stock['id'],
            "food" => $stock['food_name'],
            "location" => $isFreezer ? 'freezer' : 'fridge'
        ];
    }

    // 綺麗なJSONにしてフロントエンドのJSに結果を戻す
    echo json_encode([
        "success" => true,
        "ingredients" => $matchingResults,
        "all_stocks" => $formattedAllStocks
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "処理中にエラーが発生しました: " . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
