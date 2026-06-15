<?php
//食材を検索（サーバー側）

function serch_ingredients($food_neme){
        
// ==================================================================================
// page/food_search_server.php （表記ゆれ対応：漢字・ひらがな・カタカナ相互検索版）
// ==================================================================================

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../helpers/utils.php'; 

try {
    $pdo = getPDO();
    
    $keyword = isset($food_neme)?? '';

    if ($keyword === '') {
        echo json_encode(['success' => true, 'items' => []], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ==========================================
    // 💡 表記ゆれ対策：キーワードのバリエーションを作成
    // ==========================================
    // 1. 入力されたそのままのキーワード（例：「人参」や「にんじん」）
    $kw_original = $keyword;

    // 2. カタカナ・全角英数を「ひらがな」に変換（例：「ニンジン」➔「にんじん」）
    $kw_hiragana = mb_convert_kana($keyword, "c", "UTF-8");

    // 3. ひらがな・全角英数を「全角カタカナ」に変換（例：「にんじん」➔「ニンジン」）
    $kw_katakana = mb_convert_kana($keyword, "C", "UTF-8");

    // ==========================================
    // SQLクエリの組み立て（OR で複数の可能性を検索）
    // ==========================================
    $sql = "
        SELECT 
            i.id,
            i.food_name,
            i.quantity,
            i.unit,
            i.expiration_date,
            i.term_type,
            i.storage_location_id,
            i.category_id
        FROM 
            ingredients i
        WHERE 
            i.user_id = :user_id
            AND (
                i.food_name LIKE :kw_orig 
                OR i.food_name LIKE :kw_hira 
                OR i.food_name LIKE :kw_kata
            )
        ORDER BY 
            i.expiration_date ASC
    ";

    $stmt = $pdo->prepare($sql);

    $current_user_id = 1; 

    // それぞれを LIKE 検索用のワイルドカード（%）で挟む
    $stmt->execute([
        ':user_id' => $current_user_id,
        ':kw_orig' => '%' . $kw_original . '%',
        ':kw_hira' => '%' . $kw_hiragana . '%',
        ':kw_kata' => '%' . $kw_katakana . '%'
    ]);

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'count'   => count($results),
        'items'   => $results
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'データベース検索中にエラーが発生しました: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

}

?>