<?php
$current = basename($_SERVER['PHP_SELF']);

?>

<button class="hamburger" onclick="toggleMenu()" id="hamburger">
    <span></span>
    <span></span>
    <span></span>
</button>

<div class="sidebar" id="sidebar">

    <div class="logo">
        FridgeAI
    </div>


    <div class="menu">

        <a href="home.php"
            class="<?= ($page ?? '') === 'home' ? 'active' : '' ?>">
            🏠 ホーム
        </a>

        <a href="fridge.php"
            class="<?= ($page ?? '') === 'fridge' ? 'active' : '' ?>">
            🥬 冷蔵庫管理
        </a>

        <a href="food_register.php"
            class="<?= ($page ?? '') === 'food' ? 'active' : '' ?>">
            📷 食材登録
        </a>


        <a href="AI_recipe.php"
            class="<?= ($page ?? '') === 'AI_recipe' ? 'active' : '' ?>">
            🤖 AIレシピ
        </a>

        <a href="food_loss.php"
            class="<?= ($page ?? '') === 'analytics' ? 'active' : '' ?>">
            📊 食品ロス分析
        </a>

        <a href="settings.php"
            class="<?= ($page ?? '') === 'settings' ? 'active' : '' ?>">
            ⚙️ 設定
        </a>

    </div>

</div>