<?php
// ==================================================================================
// AI_info/ai_rule_scan.php （バグ完全修正・環境依存誤変換回避版）
// ==================================================================================

function scan_rule($base64_image, $api_key)
{
    // プレフィックス (data:image/jpeg;base64,) があれば除去
    // 💡 環境の自動リンク化を避けるため、正規表現のデリミタに # を使用します
    if (preg_match('#^data:image/(\w+);base64,#', $base64_image, $type)) {
        $base64_image = substr($base64_image, strpos($base64_image, ',') + 1);
    }

    // 1. プロンプトの定義
    $prompt = "添付されたレシート画像から購入された商品をすべて抽出し、以下の【JSONフォーマット】のみを出力してください。\n"
        . "解説、Markdownの枠（```json や ``` など）、挨拶、導入文、注釈は絶対に一切出力しないでください。文字列の先頭は必ず「{」で始め、最後は必ず「}」で終わる純粋なJSONデータのみを出力してください。\n\n"
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
        . "6. term_type: 傷みやすい生鮮食品（肉・魚・惣菜など）は 『use_by』、それ以外の長持ちするもの（卵・野菜・調味料・日用品など）は 『best_before』 としてください。\n\n"
        . "【JSONフォーマット】\n"
        . "{\n"
        . "  \"items\": [\n"
        . "    {\n"
        . "      \"food_name\": \"キャベツ\",\n"
        . "      \"price\": 158,\n"
        . "      \"quantity\": 1,\n"
        . "      \"estimated_weight\": 0,\n"
        . "      \"shelf_life_days\": 7,\n"
        . "      \"term_type\": \"best_before\"\n"
        . "    }\n"
        . "  ]\n"
        . "}";

    // 2. エンドポイントURL構築
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $api_key;

    // 3. ペイロードの組み立て
    $payload = [
        "contents" => [
            [
                "parts" => [
                    ["text" => $prompt],
                    [
                        "inline_data" => [
                            "mime_type" => "image/jpeg",
                            "data" => $base64_image
                        ]
                    ]
                ]
            ]
        ]
    ];

    // 4. cURL通信の実行
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
        error_log("Gemini cURL Error: " . $error);
        return false;
    }

    $response_data = json_decode($response, true);

    if (isset($response_data['error'])) {
        error_log("Gemini API Error: " . json_encode($response_data['error']));
        return false;
    }

    $ai_text = $response_data['candidates'][0]['content']['parts'][0]['text'] ?? null;
    if (!$ai_text) {
        error_log("Gemini Response Text is empty. Full Response: " . $response);
        return false;
    }

    // 5. AIのテキストから不要な文字やMarkdownの枠、余白を徹底的に掃除
    $ai_text = trim($ai_text);

    // 💡 エディタの誤認識バグ(URL自動変換)を防ぐため、単純な文字列置換とシンプルな正規表現に変更しました
    $ai_text = str_replace("```json", "", $ai_text);
    $ai_text = str_replace("```JSON", "", $ai_text);
    $ai_text = str_replace("```", "", $ai_text);
    $ai_text = trim($ai_text);

    $start_pos = strpos($ai_text, '{');
    $end_pos = strrpos($ai_text, '}');
    if ($start_pos !== false && $end_pos !== false) {
        $ai_text = substr($ai_text, $start_pos, $end_pos - $start_pos + 1);
    }

    // 6. JSONデコードを実行
    $result = json_decode($ai_text, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON Parse Failed. Error Code: " . json_last_error() . " / Raw AI Text: " . $ai_text);
        return false;
    }

    return $result;
}
