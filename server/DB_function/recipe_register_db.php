<?php
// ==================================================================================
// DB_function/ai_recipe_db.php （冷蔵庫在庫の取得処理）
// ==================================================================================

/**
 * ingredientsテーブルから賞味期限が古い順にすべての食材を取得し、AI用データ構造で返す
 * * @return array 成功時: 在庫配列, 失敗時: ['error' => 'エラーメッセージ']
 */
function get_ingredients()
{
    require_once __DIR__ . "/../../helpers/utils.php";
    try {
        // utils.php の共通 PDO を取得
        $pdo = getPDO();

        // ingredients テーブルから賞味期限が古い順（ASC）にすべての食材を取得
        $sql = "SELECT 
                    i.id, 
                    i.food_name, 
                    i.quantity, 
                    i.expiration_date, 
                    i.term_type, 
                    s.location_name 
                FROM ingredients i
                JOIN storage_locations s ON i.storage_location_id = s.id
                ORDER BY i.expiration_date ASC";

        $stmt = $pdo->query($sql);
        $all_ingredients = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // AIに渡しやすいように連想配列の形に整える
        return [
            'success' => true,
            'ingredients' => $all_ingredients
        ];
    } catch (PDOException $e) {
        // コントローラー側で判定できるようにエラーメッセージを返す
        return [
            'success' => false,
            'error' => 'データベースエラーが発生しました: ' . $e->getMessage()
        ];
    }
}
