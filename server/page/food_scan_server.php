<?php
// ===================================================
// page/food_scan_server.php （表記揺れ対策・プロンプト強化版）
// ===================================================

// 🚀 AIの解析時間をしっかりと確保するため、制限時間を「無制限」に設定
set_time_limit(30);

header('Content-Type: application/json; charset=utf-8');

// 🛠️ 1. 各種ヘルパーファイルの読み込み
require_once __DIR__ . '/../../helpers/gemini_api.php';
require_once __DIR__ . '/../../helpers/utils.php';

// 🛠️ 2. 【超確実】このファイルから直接ルートの .env を狙い撃ちして読み込む処理
$envPath = __DIR__ . '/../../.env'; // server/page/ から2つ上がプロジェクトルート

if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $clean_key = trim($key);
            $clean_value = trim(trim($value), '"\'');

            // 確実にこのファイル内の $_ENV に保存
            $_ENV[$clean_key] = $clean_value;
        }
    }
}

// 💡 3. 格納した $_ENV から直接引き出す
$api_key = $_ENV['GEMINI_API_KEY'] ?? '';

// フロントからのJSONデータを取得
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['error' => 'リクエストデータが空です'], JSON_UNESCAPED_UNICODE);
    exit;
}

$action = $input['action'] ?? 'scan';

// ---------------------------------------------------
// 🔥 処理パターンA：レシート画像をAIでスキャンする
// ---------------------------------------------------
if ($action === 'scan') {
    if (!isset($input['image'])) {
        echo json_encode(['error' => '画像データが送信されていません'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $base64_image = preg_replace('#^data:image/\w+;base64,#i', '', $input['image']);

    // 物価データ
    $market_today = [
        ["category" => "肉類", "keyword" => "豚バラ, 豚ロース, 豚肉", "unit_price_100g" => 240],
        ["category" => "肉類", "keyword" => "鶏むね, 鶏もも, 鶏肉", "unit_price_100g" => 98],
        ["category" => "肉類", "keyword" => "牛バラ, 牛肉", "unit_price_100g" => 350],
        ["category" => "野菜", "keyword" => "キャベツ", "unit_price_per_piece" => 200, "standard_weight_g" => 1000],
        ["category" => "野菜", "keyword" => "レタス", "unit_price_per_piece" => 160, "standard_weight_g" => 400],
        ["category" => "野菜", "keyword" => "大根, だいこん", "unit_price_per_piece" => 150, "standard_weight_g" => 800],
        ["category" => "野菜", "keyword" => "玉ねぎ, たまねぎ", "unit_price_per_piece" => 60, "standard_weight_g" => 200]
    ];
    $market_context = json_encode($market_today, JSON_UNESCAPED_UNICODE);

    // 💡 プロンプトを強化：AIに一般的な食材名へ名寄せ（正規化）させるルールを追加
    $prompt = "提供されたレシート画像から、購入された「具体的な食品名」と「その支払金額」をすべて認識してください。\n\n"
        . "その後、以下の【本日の物価データ】を参考にして、各食品の「おおよその重量（グラム数）」を厳格に計算・予測してください。\n\n"
        . "【本日の物価データ】:\n"
        . $market_context . "\n\n"
        . "【重量の計算・予測ルール】:\n"
        . "1. 肉類（豚・鶏・牛など）の場合:\n"
        . "   レシートの「金額」÷ 【本日の物価データ】の「unit_price_100g」× 100 ＝ 予測グラム数として四捨五入して算出してください。\n"
        . "2. 個数売りの野菜（キャベツや玉ねぎなど）の場合:\n"
        . "   レシートの金額から購入個数を推測し、個数 × 「standard_weight_g」＝ 予測グラム数として算出してください。\n"
        . "3. 物価データに存在しない未知の食品、惣菜などの場合:\n"
        . "   その金額帯の一般的な食品のボリュームから、現実的なグラム数をあなたの知識ベースから妥当に割り出してください。\n\n"
        . "【★最重要★ 食品名の表記ゆれ統一ルール】:\n"
        . "DB登録時の重複を防ぐため、レシートに記載された商品名をそのまま出力せず、必ず以下の【代表的な一般的な名称（漢字優先）】に名寄せ・変換して `food_name` に格納してください。\n"
        . "- 「たまご」「タマゴ」「玉子」など ➔ 一律で『卵』に統一\n"
        . "- 「豚バラ」「豚ロース」「ぶた」「ポーク」など ➔ 一律で『豚肉』に統一\n"
        . "- 「鶏もも」「鶏むね」「とり」「チキン」など ➔ 一律で『鶏肉』に統一\n"
        . "- 「牛バラ」「カナダ産牛」など ➔ 一律で『牛肉』に統一\n"
        . "- 「キャベツ半玉」やカットされた野菜 ➔ 一律で『キャベツ』『大根』『玉ねぎ』などの単体食材名に統一\n"
        . "その他の食材についても、ひらがな・カタカナ・店舗独自の略称（例: ニンジン、にんじん、人参）が混在しないよう、最も一般的で簡潔な『漢字の名称（人参）』、または『カタカナの名称（レタス）』に補正してください。商品名にメーカー名や産地、サイズ（大・小・徳用など）が含まれている場合はすべて排除してください。";

    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $api_key;

    $payload = [
        "contents" => [
            ["parts" => [["text" => $prompt], ["inline_data" => ["mime_type" => "image/jpeg", "data" => $base64_image]]]]
        ],
        "generationConfig" => [
            "responseMimeType" => "application/json",
            "responseSchema" => [
                "type" => "object",
                "properties" => [
                    "items" => [
                        "type" => "array",
                        "items" => [
                            "type" => "object",
                            "properties" => [
                                "food_name" => ["type" => "string"],
                                "price" => ["type" => "integer"],
                                "estimated_weight" => ["type" => "integer"]
                            ],
                            "required" => ["food_name", "price", "estimated_weight"]
                        ]
                    ]
                ]
            ]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        echo json_encode(['error' => 'API通信エラー: ' . $error], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $response_data = json_decode($response, true);
    if (isset($response_data['error'])) {
        echo json_encode(['error' => 'Gemini応答エラー', 'details' => $response_data['error']], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $ai_result_raw_json = $response_data['candidates'][0]['content']['parts'][0]['text'] ?? null;
    if (!$ai_result_raw_json) {
        echo json_encode(['error' => 'Geminiからの応答の解析に失敗しました。'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo $ai_result_raw_json;
    exit;
}

// ---------------------------------------------------
// 🔥 処理パターンB：修正・確定されたデータをDBに保存する
// ---------------------------------------------------
if ($action === 'save') {
    $items = $input['items'] ?? [];
    if (empty($items)) {
        echo json_encode(['error' => '保存するデータがありません'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    try {
        $pdo = getPDO();

        $stmt = $pdo->prepare("
            INSERT INTO ingredients (user_id, category_id, food, quantity)
            VALUES (:user_id, :category_id, :food, :quantity)
        ");

        $current_user_id = 1;

        foreach ($items as $item) {
            $categoryId = 4; // その他
            $name = $item['food_name'];

            if (preg_match('/(豚|鶏|牛|肉|ハンバーグ|ひき肉)/u', $name)) {
                $categoryId = 1;
            } elseif (preg_match('/(キャベツ|玉ねぎ|たまねぎ|大根|レタス|野菜|トマト|人参)/u', $name)) {
                $categoryId = 2;
            } elseif (preg_match('/(サーモン|鮭|サバ|魚|エビ|イカ)/u', $name)) {
                $categoryId = 3;
            }

            $stmt->execute([
                ':user_id'     => $current_user_id,
                ':category_id' => $categoryId,
                ':food'        => $name,
                ':quantity'    => (int)$item['estimated_weight']
            ]);
        }

        echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['error' => 'DB登録中にエラーが発生しました: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
