<?php
// ==================================================================================
// server/page/AI_recipe_server.php （サーバー側：外部関数連携・最終版）
// ==================================================================================

// エラー報告を有効化（デバッグ用）
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

try {
    // 1. プロジェクト共通の便利関数（getPDO）とAPIヘルパーを読み込む
    // 💡 もし recipe_rule() が rules.php など別のファイルにある場合は、ここに require_once を追加してください
    require_once __DIR__ . '/../../helpers/utils.php';
    require_once __DIR__ . '/../../helpers/gemini_api.php';
    require_once __DIR__ . '/../AI_info/ai_rule_recipe.php';

    // 2. .env から読み込まれたAPIキーを取得
    $api_key = $_ENV['GEMINI_API_KEY'] ?? $_SERVER['GEMINI_API_KEY'] ?? '';

    if (empty($api_key)) {
        throw new Exception(".env ファイルから Gemini API キーを取得できませんでした。");
    }

    // 3. フロント（JS）からポストされた調理条件の取得
    $dish_type = $_POST['dish_type'] ?? '指定なし';     // 例：朝ごはん、おやつ、夜ごはん
    $time_limit = $_POST['time_limit'] ?? '指定なし';   // 例：15分以内、30分以内
    $cooking_options_text = "食種: {$dish_type}, 調理時間: {$time_limit}";

    // 4. utils.php の共通関数 getPDO() を使ってデータベース接続を取得
    $pdo = getPDO();

    $userId = 1; // 💡 実際のログインセッション等があれば $_SESSION['user_id'] 等に差し替えてください

    // 5. 【カレー地獄脱出シチュエーション】
    // 賞味期限が近い古いもの20件をベースに、その中からランダムに12件を抽出してAIに渡す
    $sql = "SELECT * FROM (
                SELECT ingredient_id, food_name, quantity, unit 
                FROM ingredient 
                WHERE user_id = :user_id AND quantity > 0 
                ORDER BY expiration_date ASC 
                LIMIT 20
            ) as sub_query
            ORDER BY RAND()
            LIMIT 12";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':user_id' => $userId]);
    $fridge_ingredients = $stmt->fetchAll();

    if (empty($fridge_ingredients)) {
        throw new Exception("冷蔵庫に食材が登録されていません。食材を登録してから再度お試しください。");
    }

    // AIに読み込ませるために、取得した厳選在庫をJSON文字列化
    $fridge_ingredients_json = json_encode($fridge_ingredients, JSON_UNESCAPED_UNICODE);

    // 6. 外部の recipe_rule() からプロンプトの土台を取得して Gemini API を実行
    $ai_result = generate_ai_recipes($fridge_ingredients_json, $cooking_options_text, $api_key);

    if (!$ai_result || !isset($ai_result['success']) || !$ai_result['success']) {
        throw new Exception("AIシェフがレシピを正常に生成できませんでした。再度お試しください。");
    }

    // 7. フロント（JS）へ、AIの返答と「元の在庫データ（ID含む）」をまとめて返却
    $formatted_ingredients = [];
    foreach ($fridge_ingredients as $ing) {
        $formatted_ingredients[] = [
            'id' => $ing['ingredient_id'],
            'food' => $ing['food_name'],
            'quantity' => $ing['quantity'],
            'unit' => $ing['unit']
        ];
    }

    echo json_encode([
        'success' => true,
        'recipes' => $ai_result['recipes'],
        'original_ingredients' => $formatted_ingredients
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

// ==================================================================================
// 【Gemini API レシピ専用通信関数】responseSchema完全導入版
// ==================================================================================
function generate_ai_recipes($fridge_ingredients_json, $cooking_options_text, $api_key)
{
    // 👑 別のファイルに定義されている recipe_rule() をここで安全に呼び出します
    // 💡 もし読み込み先が足りなくてエラーになる場合は、ファイル最上部でそのファイルを require してください
    $base_rule = recipe_rule();

    $prompt = $base_rule . "\n\n"
        . "【入力データ】\n"
        . "■ 現在の在庫データ:\n" . $fridge_ingredients_json . "\n\n"
        . "■ ユーザーの調理条件:\n" . $cooking_options_text;

    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $api_key;

    $payload = [
        "contents" => [
            [
                "parts" => [
                    ["text" => $prompt]
                ]
            ]
        ],
        "generationConfig" => [
            "responseMimeType" => "application/json",
            "responseSchema" => [
                "type" => "OBJECT",
                "properties" => [
                    "success" => ["type" => "BOOLEAN"],
                    "recipes" => [
                        "type" => "ARRAY",
                        "description" => "提案する3つのレシピリスト",
                        "items" => [
                            "type" => "OBJECT",
                            "properties" => [
                                "recipe_name" => ["type" => "STRING", "description" => "料理名"],
                                "description" => ["type" => "STRING", "description" => "消費した在庫をサラッと書いた短い説明文（1文限定）"],
                                "features" => [
                                    "type" => "ARRAY",
                                    "items" => ["type" => "STRING"],
                                    "description" => "特徴タグ（最大2つ。例: ヘルシー, 15分以内）"
                                ],
                                "used_ingredients" => [
                                    "type" => "ARRAY",
                                    "description" => "この料理で消費する食材のリスト（ID不要）",
                                    "items" => [
                                        "type" => "OBJECT",
                                        "properties" => [
                                            "name" => ["type" => "STRING", "description" => "使用する食材名。必ず『ひらがな』に変換すること。例：たまねぎ、ぶたにく"],
                                            "quantity" => ["type" => "NUMBER", "description" => "消費する数量（数値）"]
                                        ],
                                        "required" => ["name", "quantity"]
                                    ]
                                ]
                            ],
                            "required" => ["recipe_name", "description", "features", "used_ingredients"]
                        ]
                    ]
                ],
                "required" => ["success", "recipes"]
            ]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200) {
        error_log("Gemini API HTTP Error: " . $http_code . " / Response: " . $response);
        return false;
    }

    $response_data = json_decode($response, true);
    $ai_text = $response_data['candidates'][0]['content']['parts'][0]['text'] ?? null;

    if (!$ai_text) {
        return false;
    }

    return json_decode(trim($ai_text), true);
}
