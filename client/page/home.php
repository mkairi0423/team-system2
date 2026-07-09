<?php

$title = "ホーム";
$page = "home";

include "template/header.php";
include "template/sidebar.php";

session_start();

$_SESSION['user']['user_id'] = 1; //TODO:ここは消す

// データベース接続と関数の読み込み
require_once __DIR__ . "/../../helpers/def.php";
require_once __DIR__ . "/../../helpers/utils.php";
require_once __DIR__ . "/../../server/page/get_alert_server.php";


// ユーザーIDの存在を確認
hasUserId();

$pdo = getPDO();
$userId = $_SESSION['user']['user_id'] ?? null;

// 🟢 期限が迫っている食材データをフル取得 (最大6件)
$urgent_ingredients = getUrgentIngredients($pdo, $userId, 6);

// 統計用の総登録数を取得
$total_count = getTotalIngredientCount($pdo, $userId);

// 冷凍ストック・お気に入りレシピ数を取得
$frozen_count = getFrozenStockCount($pdo, $userId);
$fav_recipe_count = getFavoriteRecipeCount($pdo, $userId);

// アラート対象（残り3日以内、またはすでに期限切れの食材）の件数をカウント
$alert_count = 0;
foreach ($urgent_ingredients as $food) {
    if ($food['days_left'] <= 3) {
        $alert_count++;
    }
}
//debug：セッションにuser_idが保存されているかの確認
// echo $_SESSION['user_id'];
?>

<div class="main">

    <div class="topbar">
        <h1>ホーム</h1>
    </div>

    <div class="hero">
        <h2>AIが<?= $alert_count ?>件の食品ロスを検知しました</h2>
        <p>
            賞味期限の近い食材を使ったレシピを提案できます。
        </p>
    </div>

    <div class="section">
        <div class="panel">
            <h2>賞味期限アラート</h2>

            <?php if (empty($urgent_ingredients)): ?>
                <p style="color: #888; padding: 10px 0;">現在、期限が近い食材はありません。</p>
            <?php else: ?>
                <?php foreach ($urgent_ingredients as $food): ?>
                    <?php if ($food['storage_type'] !== 'frozen'): ?>
                        <div class="food">
                            <span>📦 <?= htmlspecialchars($food['food_name'], ENT_QUOTES, 'UTF-8') ?></span>

                            <?php if ($food['days_left'] < 0): ?>
                                <span class="badge red">期限切れ (<?= abs($food['days_left']) ?>日経過)</span>
                            <?php elseif ($food['days_left'] == 0): ?>
                                <span class="badge red">本日まで</span>
                            <?php elseif ($food['days_left'] <= 2): ?>
                                <span class="badge orange">残り<?= $food['days_left'] ?>日</span>
                            <?php else: ?>
                                <span class="badge green">残り<?= $food['days_left'] ?>日</span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <h2>❄️ 冷凍焼けの可能性</h2>

        <?php
        $has_frozen = false;
        foreach ($urgent_ingredients as $food) {
            if ($food['storage_type'] === 'frozen') {
                $has_frozen = true;
                break;
            }
        }
        ?>

        <?php if (!$has_frozen): ?>
            <p style="color: #888; padding: 10px 0;">冷凍品質が低下している食材はありません。</p>
        <?php else: ?>
            <?php foreach ($urgent_ingredients as $food): ?>
                <?php if ($food['storage_type'] === 'frozen'): ?>
                    <div class="food">
                        <span>❄️ <?= htmlspecialchars($food['food_name'], ENT_QUOTES, 'UTF-8') ?></span>

                        <?php if ($food['days_left'] <= 0): ?>
                            <span class="badge red">品質低下リスク大</span>
                        <?php elseif ($food['days_left'] <= 5): ?>
                            <span class="badge orange">残り<?= $food['days_left'] ?>日</span>
                        <?php else: ?>
                            <span class="badge green">残り<?= $food['days_left'] ?>日</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="panel recipe" id="ai-recipe-panel">
        <h2>AIおすすめ</h2>
        <br>
        <h3 id="ai-recipe-name">野菜オムレツ</h3>
        <p id="ai-recipe-desc">
            牛乳・卵・レタスを消費できます。
        </p>
        <div id="ai-recipe-features" style="margin: 10px 0;"></div>
        <button class="btn" id="ai-recipe-btn">
            レシピを見る
        </button>
    </div>

    <div class="grid">

        <div class="card">
            <h3>登録食材</h3>
            <div class="value"><?= $total_count ?></div>
        </div>

        <div class="card">
            <h3>期限間近</h3>
            <div class="value"><?= $alert_count ?></div>
        </div>

        <div class="card">
            <h3>冷凍ストック</h3>
            <div class="value"><?= $frozen_count ?>品</div>
        </div>

        <div class="card">
            <h3>お気に入りレシピ</h3>
            <div class="value"><?= $fav_recipe_count ?>件</div>
        </div>

    </div>

</div>

<script src="../js/home_recipe.js"></script>

<?php include "template/footer.php"; ?>