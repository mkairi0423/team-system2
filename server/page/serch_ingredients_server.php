<?php
// ==================================================================================
//TODO:ここでひらがなにしている
// page/food_search_server.php （表記ゆれ対応・最新DB構造 適合版）
// ==================================================================================

/**
 * 食材をあいまい検索（ひらがな・カタカナ表記ゆれ対応）してJSONを出力する
 * @param string|null $food_name 検索キーワード
 */
function serch_ingredients($food_name)
{
    header('Content-Type: application/json; charset=utf-8');
    require_once __DIR__ . '/../../helpers/utils.php';

    try {
        // 🟢 共通の getPDO() を使用
        $pdo = getPDO();

        // ⚠️ 修正：isset()?? はPHPの挙動として不具合（true/falseが代入される）になるため、
        // 引数が空でなければトリムして代入、空なら空文字にする安全な記述に変更します。
        $keyword = !empty($food_name) ? trim($food_name) : '';

        if ($keyword === '') {
            echo json_encode(['success' => true, 'count' => 0, 'items' => []], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // ==========================================
        // 💡 表記ゆれ対策：キーワードのバリエーションを作成
        // ==========================================
        // 1. 入力されたそのままのキーワード（例：「人参」）
        $kw_original = $keyword;

        // 2. カタカナ・全角英数を「ひらがな」に変換（例：「ニンジン」➔「にんじん」）
        $kw_hiragana = mb_convert_kana($keyword, "c", "UTF-8");

        // 3. ひらがな・全角英数を「全角カタカナ」に変換（例：「にんじん」➔「ニンジン」）
        $kw_katakana = mb_convert_kana($keyword, "C", "UTF-8");

        // ==========================================
        // SQLクエリの組み立て（単数形テーブル・新しい主キーへ変更）
        // ==========================================
        $sql = "
            SELECT 
                i.ingredient_id, 
                i.food_name,
                i.quantity,
                i.unit,
                i.expiration_date,
                i.term_type,
                i.storage_location_id,
                i.category_id
            FROM 
                ingredient i
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

        // セッションからログインユーザーIDを取得（なければデフォルト 1）
        $current_user_id = $_SESSION['user']['uid'] ?? 1;

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
