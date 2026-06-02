<div class="sidebar">

    <div class="logo">
        FridgeAI
    </div>

    <div class="menu">

        <a href="home.php"
            class="<?= ($page ?? '') === 'home' ? 'active' : '' ?>">
            🏠 ダッシュボード
        </a>

        <a href="fridge.php"
            class="<?= ($page ?? '') === 'fridge' ? 'active' : '' ?>">
            🥬 冷蔵庫管理
        </a>

        <a href="food_register.php"
            class="<?= ($page ?? '') === 'food_add' ? 'active' : '' ?>">
            📷 食材登録
        </a>

        <a href="barcode.php"
            class="<?= ($page ?? '') === 'barcode' ? 'active' : '' ?>">
            📦 バーコード登録
        </a>

        <a href="recipe_select.php"
            class="<?= ($page ?? '') === 'recipe' ? 'active' : '' ?>">
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