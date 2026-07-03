<?php
// 1. エラー表示を最大レベルで強制出力する設定
ini_set('display_errors', "1");
ini_set('display_startup_errors', "1");
error_reporting(E_ALL);

// レスポンスの形式をJSONに指定
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");

// 2. データベース接続設定（★環境に合わせてここだけ修正してください）
$host = 'localhost';
$dbname = 'team_system2'; // 💡もしお使いのデータベース名が違う場合はここを書き換えてください
$username = 'root';    // XAMPPなら 'root'
$password = 'root';        // XAMPPなら空文字 ''（MAMPなら 'root'）

try {
    // PDOでデータベースに接続
    $pdo = new PDO("mysql:host={$host};dbname={$dbname};charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // 仮のユーザーID（テスト用）
    $current_user_id = 1;

    // SQL文の実行（お気に入りテーブルと食材・カテゴリテーブルを結合）
    $sql = "SELECT 
                fr.recipe_id,
                fr.recipe_name,
                fr.recipe_url,
                fr.memo,
                fr.created_at,
                fri.id AS ingredient_id,
                fri.food_name_hint,
                fri.quantity,
                fri.unit,
                c.category_name
            FROM favorite_recipe fr
            LEFT JOIN favorite_recipe_ingredient fri ON fr.recipe_id = fri.recipe_id
            LEFT JOIN category c ON fri.category_id = c.category_id
            WHERE fr.user_id = :user_id
            ORDER BY fr.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':user_id' => $current_user_id]);
    $rows = $stmt->fetchAll();

    // データをフロントで扱いやすいネスト構造に整形
    $recipes = [];
    foreach ($rows as $row) {
        $recipe_id = $row['recipe_id'];
        
        if (!isset($recipes[$recipe_id])) {
            $recipes[$recipe_id] = [
                'recipe_id'   => $row['recipe_id'],
                'recipe_name' => $row['recipe_name'],
                'recipe_url'  => $row['recipe_url'],
                'memo'        => $row['memo'],
                'created_at'  => $row['created_at'],
                'ingredients' => []
            ];
        }

        if (!empty($row['ingredient_id'])) {
            $recipes[$recipe_id]['ingredients'][] = [
                'id'             => $row['ingredient_id'],
                'food_name_hint' => $row['food_name_hint'],
                'quantity'       => $row['quantity'],
                'unit'           => $row['unit'],
                'category_name'  => $row['category_name']
            ];
        }
    }

    // JSON形式で出力
    echo json_encode(array_values($recipes), JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    // データベース接続等でエラーが起きた場合は、詳細なメッセージをJSONで返す
    http_response_code(500);
    echo json_encode([
        'error' => 'DATABASE_ERROR',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
} catch (Exception $e) {
    // その他の一般的なエラー
    http_response_code(500);
    echo json_encode([
        'error' => 'SERVER_ERROR',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}