<?php
// /server/page/food_loss_server.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 共通ヘルパー関数や定数の読み込み
require_once __DIR__ . "/../../helpers/utils.php";
require_once __DIR__ . "/../../helpers/def.php";

// ログインチェック
hasUserId();

// セッションからログイン中のユーザーIDを取得
$user_id = $_SESSION['user_id'];

// ====================================================
// 1. 変数の初期化（DB接続失敗や空データでもWarningを出さない防波堤）
// ====================================================
$current_month_waste = 0;
$reduction_rate = 0; // 計算前のデフォルトは0%
$alert_count = 0;

$category_data = [
    ['name' => '野菜・果物類', 'rate' => 0],
    ['name' => '卵・乳製品・大豆', 'rate' => 0],
    ['name' => '肉・魚類', 'rate' => 0]
];
$ranking_data = [];

// ====================================================
// 2. getPDO() 関数を使用してPDOオブジェクトを取得
// ====================================================
$pdo = null;
try {
    $pdo = getPDO();
} catch (Exception $e) {
    error_log("Food Loss DB Connection Error: " . $e->getMessage());
}

// ====================================================
// 3. データベースから実際の統計データを取得
// ====================================================
if ($pdo && $user_id) {
    try {
        // ① 今月の廃棄数 (status = '廃棄' 且つ updated_at が今月)
        $stmt1 = $pdo->prepare("
            SELECT COUNT(*) FROM ingredient 
            WHERE user_id = :user_id 
              AND status = '廃棄' 
              AND DATE_FORMAT(updated_at, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')
        ");
        $stmt1->execute([':user_id' => $user_id]);
        $current_month_waste = (int)$stmt1->fetchColumn();

        // 📉 ロス削減率（リアル計算：先月と今月の廃棄数を比較）
        $stmt_prev = $pdo->prepare("
            SELECT COUNT(*) FROM ingredient 
            WHERE user_id = :user_id 
              AND status = '廃棄' 
              AND DATE_FORMAT(updated_at, '%Y-%m') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m')
        ");
        $stmt_prev->execute([':user_id' => $user_id]);
        $prev_month_waste = (int)$stmt_prev->fetchColumn();

        if ($prev_month_waste > 0) {
            // 例：先月10個 -> 今月6個の場合 = (1 - 0.6) * 100 = 40% 削減
            // 今月の方が増えてしまった場合はマイナスにせず 0% とします
            $diff = $prev_month_waste - $current_month_waste;
            $reduction_rate = $diff > 0 ? (int)round(($diff / $prev_month_waste) * 100) : 0;
        } else {
            // 先月の廃棄が0の場合は、今月も0なら100%、今月廃棄があれば0%とします
            $reduction_rate = ($current_month_waste === 0) ? 100 : 0;
        }

        // ② 期限切れ予備軍（賞味期限が3日以内、または切れている未消費食材）
        $stmt2 = $pdo->prepare("
            SELECT COUNT(*) FROM ingredient 
            WHERE user_id = :user_id 
              AND status = '未消費' 
              AND expiration_date <= DATE_ADD(CURDATE(), INTERVAL 3 DAY)
        ");
        $stmt2->execute([':user_id' => $user_id]);
        $alert_count = (int)$stmt2->fetchColumn();

        // ③ カテゴリ別ロス率の取得（categoryテーブルと結合）
        $stmt3 = $pdo->prepare("
            SELECT c.category_name, 
                   COUNT(CASE WHEN i.status = '廃棄' THEN 1 END) AS waste_count,
                   COUNT(*) AS total_count
            FROM ingredient i
            INNER JOIN category c ON i.category_id = c.category_id
            WHERE i.user_id = :user_id
            GROUP BY c.category_id, c.category_name
        ");
        $stmt3->execute([':user_id' => $user_id]);
        $category_results = $stmt3->fetchAll();

        if (!empty($category_results)) {
            $category_data = [];
            foreach ($category_results as $cat) {
                $total = (int)$cat['total_count'];
                $waste = (int)$cat['waste_count'];
                $category_data[] = [
                    'name' => $cat['category_name'],
                    'rate' => $total > 0 ? round(($waste / $total) * 100) : 0
                ];
            }
        }

        // ④ 廃棄ランキング（ワースト3：カラム名を food_name に修正）
        $stmt4 = $pdo->prepare("
            SELECT food_name, COUNT(*) AS waste_count 
            FROM ingredient 
            WHERE user_id = :user_id AND status = '廃棄'
            GROUP BY food_name 
            ORDER BY waste_count DESC 
            LIMIT 3
        ");
        $stmt4->execute([':user_id' => $user_id]);
        $ranking_results = $stmt4->fetchAll();
        
        if (!empty($ranking_results)) {
            $ranking_data = $ranking_results;
        }

    } catch (Exception $e) {
        error_log("Food Loss Query Error: " . $e->getMessage());
    }
}