<?php

/**
 * cooking_server.php - 調理データ管理非同期通信サーバー
 */

// 💡 共通の getPDO() が定義されているファイルを読み込む
// ※実際のファイルパスに合わせて調整してください
require_once __DIR__ . "/../../helpers/utils.php";
require_once __DIR__ . "/../../helpers/def.php";

header('Content-Type: application/json; charset=UTF-8');

// クエリパラメータからアクションを取得
$action = isset($_GET['action']) ? $_GET['action'] : '';

// データベース接続を取得
try {
    $pdo = getPDO();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'DB接続エラーが発生しました。']);
    exit;
}

switch ($action) {

    // 💡 料理完了を押さずに離脱した際の一括キャンセル処理
    case 'cancel':
        $input = json_decode(file_get_contents('php://input'), true);
        $user_id = isset($input['user_id']) ? intval($input['user_id']) : 0;

        if ($user_id <= 0) {
            echo json_encode(['success' => false, 'message' => '不正なユーザーIDです。']);
            exit;
        }

        try {
            // 💡 既存の「cooking_now」テーブルからデータを削除
            $sql = "DELETE FROM cooking_now WHERE user_id = :user_id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();

            echo json_encode(['success' => true, 'message' => '調理データを破棄しました。']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'キャンセル処理失敗: ' . $e->getMessage()]);
        }
        exit;
        break;

    // 調理開始処理（AI抽出食材を一時保存）
    case 'start':
        $input = json_decode(file_get_contents('php://input'), true);
        $user_id = isset($input['user_id']) ? intval($input['user_id']) : 0;
        $items = isset($input['items']) ? $input['items'] : [];

        // 💡 ここに追加：新しい調理を開始する前に、そのユーザーの「調理中データ」を一旦全消去する
        $stmt_clear = $pdo->prepare("DELETE FROM cooking_now WHERE user_id = :user_id");
        $stmt_clear->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt_clear->execute();

        if ($user_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ユーザーIDが不正です。']);
            exit;
        }

        try {
            foreach ($items as $item) {
                // 💡 カラムにデータを入れる前に、連想配列にキーが存在するか厳密にチェック
                $food     = isset($item['food']) ? $item['food'] : '不明な食材';
                $quantity = isset($item['use_quantity']) ? $item['use_quantity'] : (isset($item['quantity']) ? $item['quantity'] : 1);
                $unit     = isset($item['unit']) ? $item['unit'] : '個';

                // フロント（JS）側での名前のブレ（original_ingredient_id か ingredient_id か）を吸収
                $orig_ing_id = null;
                if (isset($item['original_ingredient_id'])) {
                    $orig_ing_id = $item['original_ingredient_id'];
                } elseif (isset($item['ingredient_id'])) {
                    $orig_ing_id = $item['ingredient_id'];
                }

                $orig_loc_id = null;
                if (isset($item['original_storage_location_id'])) {
                    $orig_loc_id = $item['original_storage_location_id'];
                } elseif (isset($item['storage_location_id'])) {
                    $orig_loc_id = $item['storage_location_id'];
                }

                $sql = "INSERT INTO cooking_now (user_id, food, quantity, unit, original_ingredient_id, original_storage_location_id) 
                        VALUES (:user_id, :food, :quantity, :unit, :original_ingredient_id, :original_storage_location_id)";

                $stmt = $pdo->prepare($sql);
                $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->bindValue(':food', $food, PDO::PARAM_STR);
                $stmt->bindValue(':quantity', $quantity);
                $stmt->bindValue(':unit', $unit, PDO::PARAM_STR);

                // NULL または 数値 として安全にバインド
                if ($orig_ing_id !== null && $orig_ing_id !== '') {
                    $stmt->bindValue(':original_ingredient_id', intval($orig_ing_id), PDO::PARAM_INT);
                } else {
                    $stmt->bindValue(':original_ingredient_id', null, PDO::PARAM_NULL);
                }

                if ($orig_loc_id !== null && $orig_loc_id !== '') {
                    $stmt->bindValue(':original_storage_location_id', intval($orig_loc_id), PDO::PARAM_INT);
                } else {
                    $stmt->bindValue(':original_storage_location_id', null, PDO::PARAM_NULL);
                }

                $stmt->execute();
            }

            // 最新のリストを取得して返す
            $sql = "SELECT * FROM cooking_now WHERE user_id = :user_id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            $data = $stmt->fetchAll();

            echo json_encode(['success' => true, 'data' => $data]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => '開始処理失敗: ' . $e->getMessage()]);
        }
        exit;
        break;

    // 現在の調理中食材リストを取得
    case 'get_list':
        $input = json_decode(file_get_contents('php://input'), true);
        $user_id = isset($input['user_id']) ? intval($input['user_id']) : 0;

        try {
            $sql = "SELECT * FROM cooking_now WHERE user_id = :user_id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            $data = $stmt->fetchAll();

            echo json_encode(['success' => true, 'data' => $data]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'リスト取得失敗: ' . $e->getMessage()]);
        }
        exit;
        break;

    // 調理完了処理
    case 'complete':
        $input = json_decode(file_get_contents('php://input'), true);
        $user_id = $input['user_id'];
        $dish_name = $input['dish_name'];

        try {
            $pdo->beginTransaction();

            // 1. 親テーブルへ保存
            $sql_h = "INSERT INTO cooking_history (user_id, dish_name) VALUES (:user_id, :dish_name)";
            $stmt_h = $pdo->prepare($sql_h);
            $stmt_h->execute([':user_id' => $user_id, ':dish_name' => $dish_name]);
            $history_id = $pdo->lastInsertId();

            // 2. 子テーブルへ保存（★ここが最も重要）
            // テーブル定義通り 'used_food_name' を指定しています
            $sql_i = "INSERT INTO cooking_history_ingredient (history_id, category_id, used_food_name, quantity, unit)
                  SELECT :history_id, IFNULL(i.category_id, 1), cn.food, cn.quantity, cn.unit
                  FROM cooking_now cn
                  LEFT JOIN ingredient i ON cn.original_ingredient_id = i.ingredient_id
                  WHERE cn.user_id = :user_id";

            $stmt_i = $pdo->prepare($sql_i);
            $stmt_i->execute([':history_id' => $history_id, ':user_id' => $user_id]);

            // 3. 調理中リストを削除
            $stmt_d = $pdo->prepare("DELETE FROM cooking_now WHERE user_id = :user_id");
            $stmt_d->execute([':user_id' => $user_id]);

            $pdo->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;
        
    // 料理の履歴
    case 'get_history':
        $input = json_decode(file_get_contents('php://input'), true);
        // GETパラメータから取得する場合
        $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

        // 💡 親と子を結合して、全データを取得するSQL
        $sql = "SELECT h.dish_name, h.cooked_at, hi.used_food_name, hi.quantity, hi.unit
            FROM cooking_history h
            JOIN cooking_history_ingredient hi ON h.history_id = hi.history_id
            WHERE h.user_id = :user_id
            ORDER BY h.cooked_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'data' => $data]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => '無効なアクションです。']);
        exit;
        break;
}
