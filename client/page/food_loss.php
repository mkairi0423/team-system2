<?php
// ビューファイル（例: food_loss.php や analytics.php）

$title = "食品ロス分析";
$page = "analytics";

include("template/header.php");
include("template/sidebar.php");

// 新しいバックエンドファイル（food_loss_server.php）の読み込み
$server_script = __DIR__ . "/../../server/page/food_loss_server.php";

if (file_exists($server_script)) {
    require_once $server_script;
} else {
    // 万が一バックエンドファイルが読み込めない場合の最低限の安全用初期値
    $current_month_waste = 0;
    $reduction_rate = 0;
    $alert_count = 0;
    $category_data = [
        ['name' => 'データなし', 'rate' => 0]
    ]; 
    $ranking_data = [];  
}
?>

<div class="main">

    <div class="topbar">
        <h1>食品ロス分析</h1>
    </div>

    <div class="grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">

        <div class="card">
            <h3>🗑️ 今月の廃棄数</h3>
            <div class="big-number"><?php echo (int)$current_month_waste; ?>個</div>
        </div>

        <div class="card">
            <h3>📉 ロス削減率</h3>
            <div class="big-number"><?php echo (int)$reduction_rate; ?>%</div>
        </div>

        <div class="card">
            <h3>⚠️ 期限切れ予備軍</h3>
            <div class="big-number"><?php echo (int)$alert_count; ?>個</div>
        </div>

    </div>

    <br>

    <div class="panel">
        <h2>📊 食品カテゴリ別ロス率</h2>
        <br>

        <?php if (!empty($category_data)): ?>
            <?php foreach ($category_data as $cat): ?>
                <div class="analysis-item" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px;">
                    <span style="width: 150px;"><?php echo htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <div class="progress" style="flex-grow: 1; background-color: #e0e0e0; border-radius: 10px; height: 15px; margin: 0 15px; overflow: hidden;">
                        <div class="bar" style="width: <?php echo (int)$cat['rate']; ?>%; background-color: #ff6b6b; height: 100%; transition: width 0.5s;"></div>
                    </div>
                    <span style="width: 40px; text-align: right;"><?php echo (int)$cat['rate']; ?>%</span>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="color: #666;">データが登録されていません。</p>
        <?php endif; ?>
    </div>

    <br>

    <div class="panel">
        <h2>🏆 廃棄ランキング</h2>
        <br>

        <?php if (!empty($ranking_data)): ?>
            <?php foreach ($ranking_data as $index => $rank): ?>
                <div class="food-row" style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee;">
                    <!-- 💡 $rank['ingredient_name'] を $rank['food_name'] に変更しました -->
                    <span><?php echo ($index + 1) . '. ' . htmlspecialchars($rank['food_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <span><?php echo (int)$rank['waste_count']; ?>回</span>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="color: #666;">まだ廃棄された食材データがありません。</p>
        <?php endif; ?>
    </div>

    <br>

    <div class="panel">
        <h2>🤖 AI分析レポート</h2>
        <br>
        <ul class="ai-report" style="line-height: 1.8; padding-left: 20px;">
            <?php if (!empty($ranking_data)): ?>
                <!-- 💡 $ranking_data[0]['ingredient_name'] を $ranking_data[0]['food_name'] に変更しました -->
                <li>「<?php echo htmlspecialchars($ranking_data[0]['food_name'], ENT_QUOTES, 'UTF-8'); ?>」の廃棄率が高くなっています。購入量や保存方法を見直してみましょう。</li>
            <?php else: ?>
                <li>現在、特に偏った廃棄傾向は見られません。素晴らしいペースです！</li>
            <?php endif; ?>
            
            <li>今週中に消費すべき食材が <strong><?php echo (int)$alert_count; ?> 件</strong> あります。早めの消費を心がけましょう。</li>
            <li>おすすめレシピを利用して、期限が近い食材を上手に使い切ることでさらなるロス削減が期待できます。</li>
        </ul>
    </div>

</div>

<?php include("template/footer.php"); ?>