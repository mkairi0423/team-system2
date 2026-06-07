<?php

$title = "ホーム";
$page = "home";

include "template/header.php";
include "template/sidebar.php";

?>

<div class="main">

    <div class="topbar">
        <h1>home</h1>
    </div>

    <div class="hero">
        <h2>AIが3件の食品ロスを検知しました</h2>
        <p>
            賞味期限の近い食材を使ったレシピを提案できます。
        </p>
    </div>

    <div class="grid">

        <div class="card">
            <h3>登録食材</h3>
            <div class="value">24</div>
        </div>

        <div class="card">
            <h3>期限間近</h3>
            <div class="value">3</div>
        </div>

        <div class="card">
            <h3>食品ロス削減</h3>
            <div class="value">¥2,340</div>
        </div>

        <div class="card">
            <h3>AI提案数</h3>
            <div class="value">18</div>
        </div>

    </div>

    <div class="section">
        <div class="panel">
            <h2>賞味期限アラート</h2>
            <div class="food">
                <span>🥛 牛乳</span>
                <span class="badge red">本日まで</span>
            </div>
            <div class="food">
                <span>🥚 卵</span>
                <span class="badge orange">残り2日</span>
            </div>
            <div class="food">
                <span>🥬 レタス</span>
                <span class="badge green">残り5日</span>
            </div>
        </div>
        <div class="panel recipe">
            <h2>AIおすすめ</h2>
            <br>
            <h3>野菜オムレツ</h3>
            <p>
                牛乳・卵・レタスを消費できます。
            </p>
            <button class="btn">
                レシピを見る
            </button>
        </div>
    </div>
</div>

<?php include "template/footer.php"; ?>