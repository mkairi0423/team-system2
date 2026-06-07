<?php

/**
 * Gemini APIを利用してレシート画像から食材データを抽出する関数
 *
 * @param string $base64_image ベース64エンコードされた画像データ
 * @param string $api_key GeminiのAPIキー
 * @return array|string 成功時は解析済み連想配列、失敗時はエラーメッセージ文字列
 */
function scan_rule($base64_image, $api_key)
{
    // 1. プロンプトの定義
    $prompt = "添付されたレシート画像から購入された商品をすべて抽出し、以下の【JSONフォーマット】のみを出力してください。\n"
        . "解説、Markdownの枠（```json など）、挨拶は一切出力せず、生テキストのJSONオブジェクトのみを返してください。この指示に反するとシステムがクラッシュします。\n\n"
        . "【抽出・判定ルール】\n"
        . "1. food_name: メーカー名や産地を省いた一般的な名称（例：『卵』『豚肉』『きゅうり』『トイレットペーパー』など）に名寄せしてください。\n"
        . "2. price: 支払金額（数値のみ）。\n"
        . "3. estimated_weight: おおよそのグラム数（数値のみ）。肉類は金額から換算し、野菜は標準重量、トイレットペーパー等の不明な日用品は500などの数値を推測して入れてください。\n"
        . "4. shelf_life_days: あと何日日持ちするか（数値のみ）。（目安：生魚・ひき肉=2, スライス肉=3, 牛乳・惣薬=5, 野菜=7, 卵・納豆=14, 根菜=21, 日用品=1000）\n"
        . "5. term_type: 傷みやすい生鮮食品（肉・魚・惣菜など）は 『use_by』、それ以外の長持ちするもの（卵・野菜・調味料・日用品など）は 『best_before』 としてください。\n\n"
        . "【JSONフォーマット】\n"
        . "{\n"
        . "  \"items\": [\n"
        . "    {\n"
        . "      \"food_name\": \"豚肉\",\n"
        . "      \"price\": 398,\n"
        . "      \"estimated_weight\": 250,\n"
        . "      \"shelf_life_days\": 3,\n"
        . "      \"term_type\": \"use_by\"\n"
        . "    }\n"
        . "  ]\n"
        . "}";

    // 2. エンドポイントURL構築（余計な記号を一切排除した生文字列）
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $api_key;

    // 3. ペイロードの組み立てx
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
        return "cURL通信エラー: " . $error;
    }

    $response_data = json_decode($response, true);

    if (isset($response_data['error'])) {
        return "Gemini API内部エラー: " . ($response_data['error']['message'] ?? '不明なエラー');
    }

    $ai_text = $response_data['candidates'][0]['content']['parts'][0]['text'] ?? null;
    if (!$ai_text) {
        return "Geminiからの応答テキスト、または構造が空です。";
    }

    // 5. AIのテキストから不要な Markdownの枠 (```json) などを安全に除去
    $ai_text = trim($ai_text);
    if (strpos($ai_text, '```') !== false) {
        $ai_text = str_replace(array('```json', '```'), '', $ai_text);
        $ai_text = trim($ai_text);
    }

    // 6. JSONデコードを実行
    $result = json_decode($ai_text, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return "AIが返したテキストが正しいJSONではありませんでした。応答内容: " . $ai_text;
    }

    return $result;
}
