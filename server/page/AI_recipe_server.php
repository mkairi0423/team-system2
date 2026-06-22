<?php
// ==================================================================================
// page/AI_recipe_server.php （AIレシピ提案・メイン管理コントローラー）
// ==================================================================================

// 共通関数とDB処理関数の読み込み
require_once __DIR__ . "/../../helpers/utils.php";
require_once __DIR__ . "/../../helpers/gemini_api.php";
require_once __DIR__ . "/../AI_info/ai_rule_recipe.php";
require_once __DIR__ . "/../DB_function/recipe_register_db.php"; // 💡 追加したDB関数ファイルを読み込む

header('Content-Type: application/json; charset=UTF-8');

// AIの返答待ちでタイムアウトしないためのセーフティ
set_time_limit(30);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ------------------------------------------------------------------------------------
    // 1. 画面から送られてきた「選択条件」を変数に格納
    // ------------------------------------------------------------------------------------
    $meal_type = $_POST['meal_type'] ?? '夜ごはん';
    $cuisine = $_POST['cuisine'] ?? '中華';
    $flavor = $_POST['flavor'] ?? '辛い系';
    $cooking_time = $_POST['cooking_time'] ?? '15分以内';
    $servings = $_POST['servings'] ?? '2人前';

    // ------------------------------------------------------------------------------------
    // 🗄️ 2. DB処理用の関数を呼び出して、冷蔵庫の在庫を取得
    // ------------------------------------------------------------------------------------
    $db_result = get_ingredients();

    // もしDB処理側でエラーが発生していた場合は、その時点でフロントへエラーJSONを返す
    if ($db_result['success'] === false) {
        echo json_encode([
            'success' => false,
            'error' => $db_result['error']
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 在庫データをJSONテキスト化
    $inventory_json = json_encode(['ingredients' => $db_result['ingredients']], JSON_UNESCAPED_UNICODE);

    // ------------------------------------------------------------------------------------
    // 🤖 3. Gemini APIの設定とプロンプトの構築
    // ------------------------------------------------------------------------------------
    if (empty($db_result['ingredients'])) {
        echo json_encode([
            'success' => true, // エラーではなく正常なレスポンスとして扱う
            'recipes' => [],   // レシピは空
            'message' => '現在、調理可能な食材がありません。食材を登録してください。'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }


    $api_key = getenv('GEMINI_API_KEY') ?: ($_ENV['GEMINI_API_KEY'] ?? ($_SERVER['GEMINI_API_KEY'] ?? ''));
    $model = 'gemini-2.5-flash';
    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$api_key}";

    $system_prompt = recipe_rule();

    $user_prompt = <<<EOD
【実行指示】：今回は「◆ タスクA：条件に沿ったAIレシピ提案」を実行してください。
※タスクBやタスクCの処理、およびフォーマットは完全に無視してください。
 
■ ユーザーが指定した調理条件
・食種: {$meal_type}
・ジャンル: {$cuisine}
・味付けの系統: {$flavor}
・調理時間: {$cooking_time}
・分量: {$servings}
 
■ 現在の食材の在庫リスト（ID付き・賞味期限の古い順）
{$inventory_json}
 
【📢 AIシェフへの場所別調理ルール】
在庫リストに含まれる「location_name（冷蔵庫、冷凍庫など）」を確認し、以下の基準でレシピを考案してください。
・「冷凍庫」の食材は、解凍が必要であることを考慮し、メインのタンパク源として積極的に使用してください。
・「冷蔵庫」や「野菜室」の食材は、賞味期限が近いものを最優先で消費してください。
 
上記の在庫リストから、賞味期限の古い食材と保管場所の特性を考慮してレシピを3つ考えて、システムルールで指定された「【出力フォーマット（タスクA）】」のJSON形式のみで出力してください。
EOD;

    // v1beta用の構造データ
    $data = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $user_prompt]
                ]
            ]
        ],
        'systemInstruction' => [
            'parts' => [
                ['text' => $system_prompt]
            ]
        ],
        'generationConfig' => [
            'responseMimeType' => 'application/json'
        ]
    ];

    // ------------------------------------------------------------------------------------
    // 🚀 4. AI通信（cURL）を実行する
    // ------------------------------------------------------------------------------------
    try {
        $ch = curl_init($url);
        if ($ch === false)
            throw new Exception('cURLの初期化に失敗しました。');

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);

        if ($response === false)
            throw new Exception(curl_error($ch));

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception("APIエラーが発生しました（コード: {$httpCode}）詳細: " . $response);
        }

        // ------------------------------------------------------------------------------------
        // 5. AIの返答を解析してフロント（画面3）へレスポンス
        // ------------------------------------------------------------------------------------
        $result = json_decode($response, true);
        $aiResponseText = $result['candidates'][0]['content']['parts'][0]['text'] ?? '[]';

        $recipe_array = json_decode($aiResponseText, true);

        echo json_encode([
            'success' => true,
            'recipes' => $recipe_array
        ], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        // 💡 try-catch の境界線を綺麗に分けました
        $errorMessage = $e->getMessage();

        // 429エラーなら親切なメッセージを返す
        $displayMessage = (strpos($errorMessage, '429') !== false)
            ? "AIの利用制限に達しました。1分ほど待ってから再度お試しください。"
            : "AI通信エラー: " . $errorMessage;

        echo json_encode([
            'success' => false,
            'error' => $displayMessage
        ], JSON_UNESCAPED_UNICODE);
    } // 💡 catch の閉じカッコをしっかり閉じる

    exit; // 💡 最後に必ず処理を終了させる
}