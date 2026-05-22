<?php
// =======================================
// OpenAI API を使ったAIレシピ提案
// localhostで動作
// =======================================

$env = parse_ini_file(__DIR__ . "/.env");

$apiKey = $env["OPENAI_API_KEY"];
echo $apiKey;


// 冷蔵庫の食材
$foods = [
    "卵",
    "牛乳",
    "玉ねぎ",
    "ベーコン"
];

// 配列 → 文字列
$foodText = implode("、", $foods);

// AIへの指示
$prompt = "
以下の食材で作れる料理を提案してください。

食材:
{$foodText}

条件:
・賞味期限が近い食材を優先
・料理名
・簡単な説明
・不足している食材も表示
";

// =======================================
// API送信データ
// =======================================
$data = [
    "model" => "gpt-4.1-mini",
    "messages" => [
        [
            "role" => "system",
            "content" => "あなたは料理アシスタントです。"
        ],
        [
            "role" => "user",
            "content" => $prompt
        ]
    ],
    "temperature" => 0.7
];

$jsonData = json_encode($data);

// =======================================
// cURL
// =======================================
$ch = curl_init();

curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.openai.com/v1/chat/completions",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $jsonData,
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "Authorization: Bearer " . $apiKey
    ],

    // SSL証明書設定
    CURLOPT_CAINFO => __DIR__ . "/cacert.pem",
]);

$response = curl_exec($ch);

// エラー用
$errorMessage = "";
$aiMessage = "";

if (curl_errno($ch)) {

    $errorMessage = "cURL Error: " . curl_error($ch);

} else {

    $result = json_decode($response, true);

    // APIエラー確認
    if (isset($result["error"])) {

        $errorMessage = "API Error: " . $result["error"]["message"];

    } elseif (isset($result["choices"][0]["message"]["content"])) {

        $aiMessage = $result["choices"][0]["message"]["content"];

    } else {

        $errorMessage = "レスポンス取得失敗";
    }
}

curl_close($ch);
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>AIレシピ提案</title>
</head>
<body>

    <h1>AIレシピ提案</h1>

    <h3>食材</h3>
    <p><?= htmlspecialchars($foodText) ?></p>

    <?php if (!empty($errorMessage)): ?>
        <h3>エラー</h3>
        <p><?= htmlspecialchars($errorMessage) ?></p>
    <?php endif; ?>

    <?php if (!empty($aiMessage)): ?>
        <h3>AIおすすめレシピ</h3>
        <p><?= nl2br(htmlspecialchars($aiMessage)) ?></p>
    <?php endif; ?>

</body>
</html>