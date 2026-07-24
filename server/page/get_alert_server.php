<?php

/**
 * ユーザーの在庫から期限が近い食材を通常・冷凍問わず統合して取得する関数
 *
 * @param PDO $pdo getPDO()で取得したコネクション
 * @param int $user_id 対象のユーザーID
 * @param int $limit 取得する最大件数
 * @return array 期限が近い順にソートされた食材の配列
 */
function getUrgentIngredients(PDO $pdo, int $user_id, int $limit = 6): array
{
    // ① 通常食材（冷蔵・常温）の賞味期限が近い順を取得
    $sql_normal = "
        SELECT 
            i.ingredient_id,
            i.food_name,
            i.expiration_date,
            'normal' as storage_type,
            DATEDIFF(i.expiration_date, CURRENT_DATE) as days_left
        FROM ingredient i
        JOIN storage_location s ON i.storage_location_id = s.location_id
        WHERE i.user_id = :user_id 
          AND i.status = '未消費'
          AND s.is_frozen = 0 
          AND i.expiration_date IS NOT NULL
        ORDER BY i.expiration_date ASC
        LIMIT :limit_normal
    ";

    // ② 冷凍食材の限界（冷凍焼けリスク）が近い順を取得
    $sql_frozen = "
        SELECT 
            i.ingredient_id,
            i.food_name,
            i.frozen_at,
            'frozen' as storage_type,
            (c.frozen_expiry_days - DATEDIFF(CURRENT_DATE, i.frozen_at)) as days_left
        FROM ingredient i
        JOIN storage_location s ON i.storage_location_id = s.location_id
        JOIN category c ON i.category_id = c.category_id
        WHERE i.user_id = :user_id 
          AND i.status = '未消費'
          AND s.is_frozen = 1 
          AND i.frozen_at IS NOT NULL
        ORDER BY days_left ASC
        LIMIT :limit_frozen
    ";

    try {
        // 通常食材の実行
        $stmt_normal = $pdo->prepare($sql_normal);
        $stmt_normal->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt_normal->bindValue(':limit_normal', $limit, PDO::PARAM_INT);
        $stmt_normal->execute();
        $normal_list = $stmt_normal->fetchAll();

        // 冷凍食材の実行
        $stmt_frozen = $pdo->prepare($sql_frozen);
        $stmt_frozen->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt_frozen->bindValue(':limit_frozen', $limit, PDO::PARAM_INT);
        $stmt_frozen->execute();
        $frozen_list = $stmt_frozen->fetchAll();

        // 2つのリストを結合
        $merged_list = array_merge($normal_list, $frozen_list);

        // 最終的に「残り日数が少ない（マイナス含む）順」にソート
        usort($merged_list, function ($a, $b) {
            return $a['days_left'] <=> $b['days_left'];
        });

        // 指定された件数だけ切り出して返す
        return array_slice($merged_list, 0, $limit);
    } catch (PDOException $e) {
        error_log("🚨 食材アラート取得エラー: " . $e->getMessage());
        return [];
    }
}

/**
 * 📊 統計用：ユーザーの総登録食材数を取得する関数
 */
function getTotalIngredientCount(PDO $pdo, int $user_id): int
{
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM ingredient WHERE user_id = :user_id AND status = '未消費'");
        $stmt->execute([':user_id' => $user_id]);
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("🚨 総食材数取得エラー: " . $e->getMessage());
        return 0;
    }
}

/**
 * 📊 統計用：期限切れ、または3日以内に期限が切れる通常食材の件数を取得
 */
function getUrgentCount(PDO $pdo, int $user_id): int
{
    try {
        // 通常食材で残り3日以下のものをカウント
        $sql = "
            SELECT COUNT(*) 
            FROM ingredient i
            JOIN storage_location s ON i.storage_location_id = s.location_id
            WHERE i.user_id = :user_id 
              AND i.status = '未消費'
              AND s.is_frozen = 0 
              AND i.expiration_date IS NOT NULL
              AND DATEDIFF(i.expiration_date, CURRENT_DATE) <= 3
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':user_id' => $user_id]);
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("🚨 期限間近数取得エラー: " . $e->getMessage());
        return 0;
    }
}

/**
 * 📊 統計用：これまでにAIが提案して実際に調理した履歴（履歴数）を取得
 */
function getAiProposalCount(PDO $pdo, int $user_id): int
{
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM cooking_history WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $user_id]);
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("🚨 AI提案調理履歴数取得エラー: " . $e->getMessage());
        return 0;
    }
}

/**
 * 📊 統計用：現在冷凍庫に入っている食材の総数を取得
 */
function getFrozenStockCount(PDO $pdo, int $user_id): int
{
    try {
        $sql = "
            SELECT COUNT(*) 
            FROM ingredient i
            JOIN storage_location s ON i.storage_location_id = s.location_id
            WHERE i.user_id = :user_id 
              AND i.status = '未消費'
              AND s.is_frozen = 1
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':user_id' => $user_id]);
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * 📊 統計用：お気に入りレシピの総数を取得
 */
function getFavoriteRecipeCount(PDO $pdo, int $user_id): int
{
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM favorite_recipe WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $user_id]);
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}