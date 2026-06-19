<?php
// ==================================================================================
// DB_function/food_scan_db.php （スキャン食材の一括保存処理）
// ==================================================================================

require_once __DIR__ . '/../../helpers/utils.php';

/**
 * AIスキャンした食材リストをデータベースに一括登録する
 * * @param int $user_id ログイン中のユーザーID
 * @param array $items フロントから届いた食材データの配列
 * @return bool 成功時: true
 * @throws PDOException DB処理に失敗した場合
 */
function scan_register(int $user_id, array $items): bool
{
    $pdo = getPDO();

    // トランザクションを開始して、すべて成功するかすべてロールバックする安全設計
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare("
            INSERT INTO ingredient (user_id, category_id, storage_location_id, food_name, quantity, unit, expiration_date, term_type)
            VALUES (:user_id, :category_id, :storage_location_id, :food_name, :quantity, :unit, :expiration_date, :term_type)
        ");

        foreach ($items as $item) {
            $name = $item['food_name'];

            // ① カテゴリID判定
            $categoryId = 4;
            if (preg_match('/(豚|鶏|牛|肉|ハンバーグ|ひき肉|ウインナー|ハム)/u', $name)) {
                $categoryId = 1;
            } elseif (preg_match('/(キャベツ|玉ねぎ|たまねぎ|大根|レタス|野菜|トマト|人参|きゅうり|ピーマン|白菜)/u', $name)) {
                $categoryId = 2;
            } elseif (preg_match('/(サーモン|鮭|サバ|魚|エビ|イカ|卵|たまご|豆腐|納豆|牛乳|チーズ)/u', $name)) {
                $categoryId = 3;
            }

            // ② 保管場所文字列 ➔ マスタID
            $storageText = $item['storage_place'] ?? '冷蔵庫';
            $storageLocationId = 1;
            if (strpos($storageText, '冷凍庫') !== false) {
                $storageLocationId = 2;
            } elseif (strpos($storageText, '常温') !== false || strpos($storageText, 'パントリー') !== false) {
                $storageLocationId = 3;
            } elseif (strpos($storageText, '野菜室') !== false) {
                $storageLocationId = 4;
            }

            // ③ 期限日
            $expirationDate = !empty($item['custom_expiry_date']) ? $item['custom_expiry_date'] : date('Y-m-d');

            // ④ 期限の種類
            $termTypeRaw = $item['term_type'] ?? 'best_before';
            $termTypeJapanese = ($termTypeRaw === 'use_by') ? '消費期限' : '賞味期限';

            // ⑤ 数量の丸め処理
            $rawQuantity = isset($item['quantity']) ? (float)$item['quantity'] : 1.0;
            $quantityValue = ($rawQuantity > 0 && $rawQuantity < 1) ? 1 : (int)round($rawQuantity);

            $unitValue = !empty($item['unit']) ? $item['unit'] : '個';

            // ENUM安全ガード
            $validUnits = ['g', '個', '本', '玉', 'パック', '枚', 'ml'];
            if (!in_array($unitValue, $validUnits, true)) {
                $unitValue = '個';
            }

            // クエリ実行
            $stmt->execute([
                ':user_id'             => $user_id,
                ':category_id'         => $categoryId,
                ':storage_location_id' => $storageLocationId,
                ':food_name'           => $name,
                ':quantity'            => $quantityValue,
                ':unit'                => $unitValue,
                ':expiration_date'     => $expirationDate,
                ':term_type'           => $termTypeJapanese
            ]);
        }

        // すべて成功したらコミット
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        // 途中でエラーが起きたら巻き戻す
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        // コントローラー側でキャッチできるように例外をそのまま投げる
        throw $e;
    }
}
