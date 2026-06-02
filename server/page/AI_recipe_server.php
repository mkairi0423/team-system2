<?php
//AI_recipe_server.php
//AI_recipe.phの処理理



// 1. ユーザーが提示してくれた共通関数ファイルを読み込む（これでgetPDOが使えるようになります）
require_once __DIR__ . "/../../helpers/utils.php"; // ※実際のファイル名に変えてください
require_once __DIR__ . "/../../helpers/gemini_api.php"; // APIキーを安全に管理するためのファイル（例: define('API_KEY', 'あなたのAPIキー');）
require_once __DIR__ . "/../AIRule/AI_Rule_API.php";

header('Content-Type: application/json; charset=UTF-8');
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
    // 🛡️ 3. 【安全装置】try-catch を使ってデータベースから在庫を取得する
    // ------------------------------------------------------------------------------------
    try {
        // ユーザーが持っている最高の道具「getPDO()」を一発呼び出し！
        $pdo = getPDO();

        // // 冷蔵庫の在庫を賞味期限が古い順に取得
        // $fridgeSql = "SELECT id, food, quantity, category_id, expiration_date FROM ingredients ORDER BY expiration_date ASC";
        // $fridgeStmt = $pdo->query($fridgeSql);
        // $fridgeItems = $fridgeStmt->fetchAll(); // 既存設定で自動的に連想配列になります

        // // 冷凍庫の在庫を登録日が古い順に取得
        // $freezerSql = "SELECT id, name, quantity, genre, created_at FROM inventories WHERE storage_type = 'freezer' ORDER BY created_at ASC";
        // $freezerStmt = $pdo->query($freezerSql);
        // $freezerItems = $freezerStmt->fetchAll();



        // 👑 修正ポイント: ingredients テーブルからのみ取得します！
        // 賞味期限が古い順（ASC）にすべての食材を取得
        $sql = "SELECT id, category_id, food, quantity, expiration_date FROM ingredients ORDER BY expiration_date ASC";
        $stmt = $pdo->query($sql);
        $all_ingredients = $stmt->fetchAll(); 

        // 👑 AIが読み込みやすいように配列の形を整えます
        $inventory = [
            'ingredients' => $all_ingredients
        ];

        // $inventory = [
        //     'fridge' => $fridgeItems,
        //     'freezer' => $freezerItems
        // ];

    } catch (PDOException $e) {
        header('Content-Type: application/json; charset=UTF-8');
        // 万が一、データベース接続などでエラーが起きたらここに強制避難！
        echo json_encode([
            'success' => false,
            'error' => 'データベースエラーが発生しました。: ' . $e->getMessage()
        ]);
        exit;

    }

    // AIに読み込ませるために在庫データをJSONテキスト化
    $inventory_json = json_encode($inventory, JSON_UNESCAPED_UNICODE);

    // ------------------------------------------------------------------------------------
    // 4. Gemini APIの設定とプロンプトの構築
    // ------------------------------------------------------------------------------------
    
    $api_key = getenv('GEMINI_API_KEY');
    $model = 'gemini-2.5-flash';
    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$api_key}";

    $system_prompt = APIRule();

    $user_prompt = <<<EOD
【実行指示】：今回は「◆ タスクA：条件に沿ったAIレシピ提案」を実行してください。
※タスクBやタスクCの処理、およびフォーマットは完全に無視してください。

■ ユーザーが指定した調理条件
・食種: {$meal_type}
・ジャンル: {$cuisine}
・味付けの系統: {$flavor}
・調理時間: {$cooking_time}
・分量: {$servings}

■ 現在の冷蔵庫・冷凍庫の在庫リスト（ID付き）
{$inventory_json}

上記の在庫リストから、賞味期限の古いもの、冷凍期間の長いものを最優先で消費できるレシピを3つ考えて、システムルールで指定された「【出力フォーマット（タスクA）】」のJSON形式（最初の1文字から最後の文字まで純粋なJSON文字列）のみで出力してください。
EOD;

    // APIに送るデータ構造の作成
    $data = [
        'contents' => [['parts' => [['text' => $user_prompt]]]],
        'systemInstruction' => ['parts' => [['text' => $system_prompt]]]
    ];

    // ------------------------------------------------------------------------------------
    // 🛡️ 5. 【安全装置】try-catch を使ってAI通信（cURL）を実行する
    // ------------------------------------------------------------------------------------
    try {
        $ch = curl_init($url);
        if ($ch === false)
            throw new Exception('cURLの初期化に失敗しました。');

        //意味: RETURNTRANSFER（戻り値を返す）を true（有効）にしています。
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);


        curl_setopt($ch, CURLOPT_POST, true);

        //意味: POSTFIELDS（送信する中身）を設定しています
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        //意味: 看板（ヘッダー）に「この荷物はJSONデータですよ」と書いておきます。これでGoogle側の受付が「お、JSONが来たな」とスムーズに受け取ってくれます。
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);



        //意味: 本来必要な「SSL証明書（通信相手が本当にGoogleかどうかの厳格なチェック）」を、学校のPCや個人の開発環境（ローカル環境）でエラーが起きないように、一時的に「チェックしなくていいよ」とスキップさせています。
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);



        //意味: curl_exec（カール・エグゼキュート = 実行）です。役割: ここでついにトラックがインターネットの海を渡ってGoogleのサーバーへ走り出します。そして、Googleが考えてくれたレシピのJSONデータを荷台に載せて帰ってきて、その中身を $response（レスポンス） という変数の中に無事に回収した瞬間
        $response = curl_exec($ch);



        if ($response === false)
            throw new Exception(curl_error($ch));

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200)
            throw new Exception("APIエラーが発生しました（コード: {$httpCode}）");

        // ------------------------------------------------------------------------------------
        // 6. 結果をフロント（画面3）へ引き渡す
        // ------------------------------------------------------------------------------------
        $result = json_decode($response, true);
        $aiResponseText = $result['candidates'][0]['content']['parts'][0]['text'] ?? '[]';

        // AIのJSON文字を配列に変換
        $recipe_array = json_decode($aiResponseText, true);

        // 画面3（フロント）へパス！
        echo json_encode([
            'success' => true,
            'recipes' => $recipe_array
        ], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        // AI通信中に何かがクラッシュしたらここに避難！
        echo json_encode([
            'success' => false,
            'error' => 'AI通信エラー: ' . $e->getMessage()
        ]);
    }
    exit;
}
?>