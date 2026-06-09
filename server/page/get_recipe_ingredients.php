<?php
// フロントエンド（JS）からの非同期通信（Fetch API）を受け付ける設定
header("Content-Type: application/json; charset=UTF-8");

// 💡 チームの共通関数ファイルを読み込む
require_once __DIR__ . "/../../helpers/utils.php"; 
// 💡【追加】APIキーを有効化するための共通ファイルを読み込む
require_once __DIR__ . "/../../helpers/gemini_api.php";

// クエリパラメータからユーザーIDと検索キーワードを取得
$userId = $_GET['user_id'] ?? null;
$keyword = $_GET['keyword'] ?? '';

if (!$userId || !$keyword) {
    echo json_encode(["success" => false, "message" => "必要なパラメータが不足しています。"]);
    exit;
}

// AIの返答待ちでタイムアウトしないためのセーフティ
set_time_limit(30);

try {
    // 💡 共通ファイルからPDOオブジェクトを取得
    $pdo = getPDO();

    // --------------------------------------------------------
    // 1. APIキー と モデル を設定 (混雑回避のため 2.0-flash を推奨)
    // --------------------------------------------------------
    $api_key = getenv('GEMINI_API_KEY') ?: ($_ENV['GEMINI_API_KEY'] ?? ($_SERVER['GEMINI_API_KEY'] ?? ''));
    $model = 'gemini-2.0-flash'; 
    
    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$api_key}";

    // プロンプトの構築
    $prompt = "「{$keyword}」を作るために一般的に必要な主要食材（調味料を除く、3〜5種類程度）のリストを教えてください。
    データベース照合を行うため、食材名は必ず漢字やカタカナ、英語を一切使わず、一般的な『ひらがな』に翻訳して出力してください。
    
    回答は余計な解説を一切含めず、以下のJSONフォーマットのみを返してください。
    [\"食材名1\", \"食材名2\", \"食材名3\"]
    
    例（カレーの場合）：[\"じゃがいも\", \"にんじん\", \"ぎゅうにく\", \"かれーるー\"]";

    // v1beta用の完璧な構造体 ＋ 純粋JSON出力設定
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
    // 3. 現在のユーザーのすべての在庫（ingredients）を全件取得【修正：locationも取得】
    // --------------------------------------------------------
    // 💡 SQL文に「location」を追加して、冷蔵庫か冷凍庫かも一緒に引っ張ってきます
    $stmt = $pdo->prepare("SELECT id, food, location FROM ingredients WHERE user_id = ?");
    $stmt->execute([$userId]);
    $userStocks = $stmt->fetchAll();

    // 比較用に、ユーザーの在庫名もお掃除（ひらがな化）してリスト化
    $cleanedStocks = [];
    foreach ($userStocks as $stock) {
        $cleanName = mb_convert_kana(trim($stock['food']), "cVs", "UTF-8");
        $cleanedStocks[] = [
            "id" => $stock['id'],
            "clean_name" => $cleanName,
            "location" => $stock['location'] ?? 'fridge' // 💡 保管場所をキープ（カラムが空なら初期値冷蔵庫）
        ];
    }

    // --------------------------------------------------------
    // 4. AIの必要食材とユーザー在庫をマッチング【修正：locationの判定を追加】
    // --------------------------------------------------------
    $matchingResults = [];

    foreach ($neededFoods as $neededFood) {
        $inStock = false;
        $stockId = null;
        $location = null; // 💡 マッチした食材の保管場所を格納する変数
        $cleanNeededFood = mb_convert_kana(trim($neededFood), "cVs", "UTF-8");

        // 部分一致で検索
        foreach ($cleanedStocks as $stock) {
            if (strpos($stock['clean_name'], $cleanNeededFood) !== false || strpos($cleanNeededFood, $stock['clean_name']) !== false) {
                $inStock = true;
                $stockId = $stock['id']; 
                $location = $stock['location']; // 💡 見つかった在庫の保管場所（fridge または freezer）を代入
                break;
            }
        }

        $matchingResults[] = [
            "id" => $stockId,      
            "food" => $neededFood,  
            "in_stock" => $inStock,
            "location" => $location // 💡 フロント（JS）へ 'fridge' / 'freezer' / null を伝える
        ];
    }

    // --------------------------------------------------------
    // 5. 綺麗なJSONにしてフロントエンドのJSに返す（全在庫も含める！）
    // --------------------------------------------------------
    echo json_encode([
        "success" => true,
        "ingredients" => $matchingResults,
        "all_stocks" => $userStocks // 💡 モーダルで使い回すための全在庫
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "処理中にエラーが発生しました: " . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>