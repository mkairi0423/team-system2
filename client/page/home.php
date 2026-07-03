<?php

$title = "ホーム";
$page = "home";

include "template/header.php";
include "template/sidebar.php";

session_start();


// データベース接続と関数の読み込み
// ※パスは実際の環境（getPDOがあるファイルなど）に合わせて調整してください
require_once __DIR__ . "/../../helpers/utils.php";
require_once __DIR__ . "/../../server/page/get_alert_server.php";

$pdo = getPDO();
// $userId = $_SESSION['user_id']; //TODO:本番環境ではこっちにする 
$userId = 1; 

// 🟢 期限が迫っている食材データをフル取得 (最大6件)
$urgent_ingredients = getUrgentIngredients($pdo, $userId, 6);

// 統計用の総登録数を取得
$total_count = getTotalIngredientCount($pdo, $userId);

// すでにある total_count や alert_count の下あたりに追加
$frozen_count = getFrozenStockCount($pdo, $userId);
$fav_recipe_count = getFavoriteRecipeCount($pdo, $userId);


// アラート対象（残り3日以内、またはすでに期限切れの食材）の件数をカウント
$alert_count = 0;
foreach ($urgent_ingredients as $food) {
    if ($food['days_left'] <= 3) {
        $alert_count++;
    }
}
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

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // ブラウザのキャッシュから直近のAI提案結果をサルベージ
        const savedRecipeData = localStorage.getItem('ai_recipe_result');

        if (savedRecipeData) {
            try {
                const result = JSON.parse(savedRecipeData);

                // 送っていただいたプロンプト（タスクA）のJSON構造をパース
                if (result.success && result.recipes && result.recipes.length > 0) {
                    const recipe = result.recipes[0];

                    // 画面上のAIおすすめ枠を最新状態へ上書き
                    document.getElementById('ai-recipe-name').innerText = recipe.recipe_name;
                    document.getElementById('ai-recipe-desc').innerText = recipe.description;

                    // 特徴タグ（features）のバッジ生成
                    const featuresContainer = document.getElementById('ai-recipe-features');
                    featuresContainer.innerHTML = '';
                    if (recipe.features && recipe.features.length > 0) {
                        recipe.features.forEach(feature => {
                            const span = document.createElement('span');
                            span.className = 'badge green';
                            span.style.marginRight = '5px';
                            span.style.fontSize = '12px';
                            span.innerText = feature;
                            featuresContainer.appendChild(span);
                        });
                    }

                    // 「レシピを見る」の挙動を結果画面（selection.php）への遷移にする
                    const recipeBtn = document.getElementById('ai-recipe-btn');
                    recipeBtn.innerText = "レシピを見る";
                    recipeBtn.addEventListener('click', () => {
                        window.location.href = 'selection.php';
                    });
                }
            } catch (e) {
                console.error("localStorageのレシピデータ解析に失敗しました:", e);
            }
        } else {
            // まだ一度もAIレシピを作っていない場合の初期挙動
            const recipeBtn = document.getElementById('ai-recipe-btn');
            recipeBtn.innerText = "新しくレシピを生成する";
            recipeBtn.addEventListener('click', () => {
                // 条件選択画面（仮）へリダイレクト
                window.location.href = 'recipe_conditions.php';
            });
        }
    });
</script>

<?php include "template/footer.php"; ?>