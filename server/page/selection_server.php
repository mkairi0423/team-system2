<?php
// 1. パスの問題を解決した外部ファイルの読み込み
require_once ('../../helpers/utils.php');

// 2. JavaScriptが正しく受信できるようにヘッダーを設定
header('Content-Type: application/json; charset=utf-8');

// デバッグ用（エラーが発生した際にJSONに割り込ませずログに残すため、画面表示はオフ）
ini_set('display_errors', 0);
error_reporting(E_ALL);

try {
    // データベース接続の確立（utils.php内の関数）
    $pdo = getPDO();
    
    //TODO: ※本来はセッション等から取得しますが、現在の仕様に合わせて固定
    $user_id = 1;

    // 3. フロント（JavaScript）から送られてきたデータ（JSON）をパース
    $raw_input = file_get_contents("php://input");
    $input = json_decode($raw_input, true);

    // 「recipe」キーが存在するかチェック
    if (!$input || !isset($input['recipe'])) {
        throw new Exception("レシピデータが正しく送信されていません。");
    }

    $recipe = $input['recipe'];

    // レシピの中に「used_ingredients」が存在するかチェック
    if (!isset($recipe['used_ingredients'])) {
        throw new Exception("レシピ内に食材リスト（used_ingredients）が見つかりません。");
    }

    $ingredients_raw = $recipe['used_ingredients'];
    $ingredients = [];

    // 4. 【最重要】二次元配列から食材の名前（文字列）だけを安全に抽出
    if (is_array($ingredients_raw)) {
        foreach ($ingredients_raw as $item) {
            if (is_array($item)) {
                if (isset($item['food_name'])) {
                    // キー名が food_name の場合
                    $ingredients[] = $item['food_name'];
                } elseif (isset($item['name'])) {
                    // キー名が name の場合（AIのデータ構造対策）
                    $ingredients[] = $item['name'];
                }
            } elseif (is_string($item)) {
                // 万が一最初からただの文字列配列として届いた場合
                $ingredients[] = $item;
            }
        }
    }

    // 最終的に有効な食材が1つ以上あるかチェック
    if (empty($ingredients)) {
        throw new Exception("消費する食材の解析に失敗したか、食材が空っぽです。");
    }

    // 7. 成功レスポンスをJSONとして返却
    echo json_encode([
        "success" => true,
        "updated_count" => 0
    ]);

} catch (Exception $e) {
    // 8. 万が一エラーが発生した場合も、必ずJSON形式でエラーを返却
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}