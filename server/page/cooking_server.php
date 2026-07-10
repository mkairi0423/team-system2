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
        $user_id = isset($input['user_id']) ? intval($input['user_id']) : 0;

        try {
            // 在庫連動等の後に一時データを削除
            $sql = "DELETE FROM cooking_now WHERE user_id = :user_id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();

            echo json_encode(['success' => true, 'message' => '調理完了、データを更新しました。']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => '完了処理失敗: ' . $e->getMessage()]);
        }
        exit;
        break;

    default:
        echo json_encode(['success' => false, 'message' => '無効なアクションです。']);
        exit;
        break;
}
