<?php
// ==================================================================================
// AI_info/ai_rule_scan.php （Response Schema導入・絶対パース失敗しない版）
// ==================================================================================

function scan_rule($base64_image, $api_key)
{
    // プレフィックス (data:image/jpeg;base64,) があれば除去
    if (preg_match('#^data:image/(\w+);base64,#', $base64_image, $type)) {
        $base64_image = substr($base64_image, strpos($base64_image, ',') + 1);
    }

    // 1. プロンプトの定義（レスポンス形式の指定が不要になり、ルールのみに集中できます）
    $prompt = "添付されたレシート画像から購入された食品・日用品をすべて抽出してください。\n\n"
        . "【抽出・判定ルール】\n"
        . "1. food_name: メーカー名や産地を省いた一般的な名称（例：『卵』『豚肉』『きゅうり』『牛乳』など）に名寄せしてください。\n"
        . "2. price: 支払金額（数値のみ）。\n"
        . "3. quantity: 購入数量（数値のみ）。半分にカットされた野菜などは 0.5 としてください。\n"
        . "   - キャベツ、レタス、白菜 ＝『玉数』（1玉なら 1、半分なら 0.5）\n"
        . "   - 大根、人参、きゅうり、長ねぎ、バナナ、牛乳、ペットボトル ＝『本数』（1本なら 1）\n"
        . "   - 肉類、魚類 ＝パック数（1パックなら 1）※ただしレシートにグラム数が明記されている場合はその数値を優先し、その場合は下記のestimated_weightを1にしてください。\n"
        . "   - 卵、納豆、豆腐、キノコ類、ヨーグルト、惣菜、日用品 ＝『パック数・個数』（1パック/1個なら 1）\n"
        . "4. estimated_weight: おおよそのグラム数（数値のみ）。肉類・魚類でレシートにグラム数の記載がない場合、金額から換算した推測値を数値で入れてください。野菜や日用品など、グラム管理しないものは 0 を入れてください。\n"
        . "5. shelf_life_days: あと何日日持ちするか（数値のみ）。（目安：生魚・ひき肉=2, スライス肉=3, 牛乳・惣菜=5, 野菜=7, 卵・納豆=14, 根菜=21, 日用品=1000）\n"
        . "6. term_type: 傷みやすい生鮮食品（肉・魚・惣菜など）は 『use_by』、それ以外の長持ちするもの（卵・野菜・調味料・日用品など）は 『best_before』 としてください。";

    // 2. エンドポイントURL構築
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $api_key;

    // 3. ペイロードの組み立て（💡 responseSchema を追加して出力形式を固定）
    $payload = [
        "contents" => [
            [
                "parts" => [
                    ["text" => $prompt],
                    [
                        "inlineData" => [
                            "mimeType" => "image/jpeg",
                            "data" => $base64_image
                        ]
                    ]
                ]
            ]
        ],
        "generationConfig" => [
            "responseMimeType" => "application/json",
            "responseSchema" => [
                "type" => "OBJECT",
                "properties" => [
                    "items" => [
                        "type" => "ARRAY",
                        "description" => "抽出されたアイテムのリスト",
                        "items" => [
                            "type" => "OBJECT",
                            "properties" => [
                                "food_name" => ["type" => "STRING", "description" => "一般的な名称に名寄せした食品名・日用品名"],
                                "price" => ["type" => "INTEGER", "description" => "支払金額（数値）"],
                                "quantity" => ["type" => "NUMBER", "description" => "購入数量。半分なら0.5"],
                                "estimated_weight" => ["type" => "INTEGER", "description" => "推測されるおよそのグラム数。肉・魚以外や不要なものは0"],
                                "shelf_life_days" => ["type" => "INTEGER", "description" => "日持ちする日数（目安に準拠）"],
                                "term_type" => [
                                    "type" => "STRING",
                                    "enum" => ["best_before", "use_by"],
                                    "description" => "期限の種類（賞味期限ならbest_before、消費期限ならuse_by）"
                                ]
                            ],
                            "required" => ["food_name", "price", "quantity", "estimated_weight", "shelf_life_days", "term_type"]
                        ]
                    ]
                ],
                "required" => ["items"]
            ]
        ]
    ];

    // 4. cURL通信の実行
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // 💡 SSL検証を完全にスキップ（ローカル環境の通信エラー対策）
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); // 👈 これを追加！

    curl_setopt($ch, CURLOPT_TIMEOUT, 60);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE); // 💡 Googleからのステータスコードを取得
    curl_close($ch);

    // ❌ cURLの通信自体が失敗している場合
    if ($error) {
        echo json_encode(['error' => 'cURL通信自体が失敗しました: ' . $error], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ❌ Gemini API（Google）側からエラーが返ってきた場合（APIキー間違いなど）
    if ($http_code !== 200) {
        echo json_encode([
            'error' => 'Gemini APIがエラーを返しました（HTTPコード: ' . $http_code . '）',
            'debug_google_raw' => json_decode($response, true) ?? $response
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($error) {
        error_log("Gemini cURL Error: " . $error);
        return false;
    }

    $response_data = json_decode($response, true);

    if (isset($response_data['error'])) {
        error_log("Gemini API Error: " . json_encode($response_data['error']));
        return false;
    }

    // 💡 確実に「生JSON文字列」だけが入ってくるため、トリミングするだけでOKに
    $ai_text = $response_data['candidates'][0]['content']['parts'][0]['text'] ?? null;
    if (!$ai_text) {
        error_log("Gemini Response Text is empty. Full Response: " . $response);
        return false;
    }

    // 5. 純粋なJSONとしてデコードを実行
    $result = json_decode(trim($ai_text), true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON Parse Failed. Error Code: " . json_last_error() . " / Raw AI Text: " . $ai_text);
        return false;
    }

    return $result;
}
