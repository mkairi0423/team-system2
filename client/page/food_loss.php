<?php

$title = "食品ロス分析";
$page = "analytics";

include("template/header.php");
include("template/sidebar.php");

?>

<div class="main">

    <div class="topbar">
        <h1>食品ロス分析</h1>
    </div>

    <!-- 統計カード -->

    <div class="grid">

        <div class="card">
            <h3>🗑️ 今月の廃棄数</h3>
            <div class="big-number">12個</div>
        </div>

        <div class="card">
            <h3>💰 節約金額</h3>
            <div class="big-number">¥4,320</div>
        </div>

        <div class="card">
            <h3>📉 ロス削減率</h3>
            <div class="big-number">35%</div>
        </div>

        <div class="card">
            <h3>⚠️ 期限切れ予備軍</h3>
            <div class="big-number">8個</div>
        </div>

    </div>

    <!-- ロス分析 -->

    <div class="panel">

        <h2>📊 食品カテゴリ別ロス率</h2>

        <br>

        <div class="analysis-item">
            <span>野菜・果物類</span>
            <div class="progress">
                <div class="bar" style="width:65%;"></div>
            </div>
            <span>65%</span>
        </div>

        <div class="analysis-item">
            <span>卵・乳製品・大豆</span>
            <div class="progress">
                <div class="bar" style="width:45%;"></div>
            </div>
            <span>45%</span>
        </div>

        <div class="analysis-item">
            <span>肉・魚類</span>
            <div class="progress">
                <div class="bar" style="width:25%;"></div>
            </div>
            <span>25%</span>
        </div>

    </div>

    <br>

    <!-- ランキング -->

    <div class="panel">

        <h2>🏆 廃棄ランキング</h2>

        <br>

        <div class="food-row">
            <span>🥬 キャベツ</span>
            <span>4回</span>
        </div>

        <div class="food-row">
            <span>🥛 牛乳</span>
            <span>3回</span>
        </div>

        <div class="food-row">
            <span>🍅 トマト</span>
            <span>2回</span>
        </div>

    </div>

    <br>

    <!-- AI分析 -->

    <div class="panel">

        <h2>🤖 AI分析レポート</h2>

        <br>

        <ul class="ai-report">

            <li>野菜の廃棄率が高くなっています。</li>

            <li>キャベツは購入後平均9日で廃棄されています。</li>

            <li>牛乳の消費忘れが多い傾向です。</li>

            <li>今週中に消費すべき食材が8件あります。</li>

            <li>おすすめレシピ利用でロス削減が期待できます。</li>

        </ul>

    </div>

</div>

<?php include("template/footer.php"); ?>

<script src="../js/analysis.js"></script>