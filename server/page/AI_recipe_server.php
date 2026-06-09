<?php
//AI_recipe_server.php
//AI_recipe.phの処理理

//AIに条件を選択してもらって、レシピを提案してもらうためのサーバー側のコード

// 1. ユーザーが提示してくれた共通関数ファイルを読み込む（これでgetPDOが使えるようになります）
require_once __DIR__ . "/../../helpers/utils.php"; // ※実際のファイル名に変えてください
require_once __DIR__ . "/../../helpers/gemini_api.php"; // APIキーを安全に管理するためのファイル（例: define('API_KEY', 'あなたのAPIキー');）
require_once __DIR__ . "/../AI_info/ai_rule_recipe.php";

header('Content-Type: application/json; charset=UTF-8');
 
// AIの返答待ちでタイムアウトしないためのセーフティ
set_time_limit(30);
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 
    // ------------------------------------------------------------------------------------
    // 2. 画面2から送られてきた「選択条件」を変数に格納
    // ------------------------------------------------------------------------------------
    $meal_type = $_POST['meal_type'] ?? '夜ごはん';
    $cuisine = $_POST['cuisine'] ?? '中華';
    $flavor = $_POST['flavor'] ?? '辛い系';
    $cooking_time = $_POST['cooking_time'] ?? '15分以内';
    $servings = $_POST['servings'] ?? '2人前';
 
    // ------------------------------------------------------------------------------------
    // 🛡️ 3. Try-catch を使ってデータベースから在庫を取得する
    // ------------------------------------------------------------------------------------
    try {
        $pdo = getPDO();
 
        // ingredients テーブルから賞味期限が古い順（ASC）にすべての食材を取得
        $sql = "SELECT id, category_id, food, quantity, expiration_date FROM ingredients ORDER BY expiration_date ASC";
        $stmt = $pdo->query($sql);
        $all_ingredients = $stmt->fetchAll();
 
        $inventory = [
            'ingredients' => $all_ingredients
        ];
 
    } catch (PDOException $e) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success' => false,
            'error' => 'データベースエラーが発生しました。: ' . $e->getMessage()
        ]);
        exit;
    }
 
    // 在庫データをJSONテキスト化
    $inventory_json = json_encode($inventory, JSON_UNESCAPED_UNICODE);
 
    // ------------------------------------------------------------------------------------
    // 4. Gemini APIの設定とプロンプトの構築（.envファイルの手動解析）
    // ------------------------------------------------------------------------------------
    
    $api_key = // 変更前
// $api_key = getenv('GEMINI_API_KEY');

// 変更後：getenvに頼らず、サーバー環境に合わせて3つの方法のどれからでも取れるようにする
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
...
・味付けの系統: {$flavor}
・調理時間: {$cooking_time}
...
・分量: {$servings}
 
■ 現在の食材の在庫リスト（ID付き・賞味期限の古い順）
{$inventory_json}
 
上記の在庫リストから、賞味期限の古い食材を最優先で消費できるレシピを3つ考えて、システムルールで指定された「【出力フォーマット（タスクA）】」のJSON形式（最初の1文字から最後の文字まで純粋なJSON文字列）のみで出力してください。
EOD;
 
    // v1beta用の完璧なキャメルケースデータ構造
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
    // 🛡️ 5. AI通信（cURL）を実行する
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
        // 6. 結果をフロント（画面3）へ引き渡す
        // ------------------------------------------------------------------------------------
        $result = json_decode($response, true);
        $aiResponseText = $result['candidates'][0]['content']['parts'][0]['text'] ?? '[]';
 
        $recipe_array = json_decode($aiResponseText, true);
 
        echo json_encode([
            'success' => true,
            'recipes' => $recipe_array
        ], JSON_UNESCAPED_UNICODE);
 
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'AI通信エラー: ' . $e->getMessage()
        ]);
    }
    exit;
}
?>