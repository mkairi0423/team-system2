<?php
$title = "冷蔵庫管理";
$page = "fridge";

include("template/header.php");
include("template/sidebar.php");
?>

<div class="main">

    <div class="topbar">
        <h1>冷蔵庫管理</h1>
    </div>

    <div class="panel">
        <h2>🧊 冷蔵庫</h2>
        <br>
        <input
            type="text"
            id="fridgeSearch"
            placeholder="🔍 食材を検索"
            class="search-box">
            <!-- 検索する処理はできている
             serch_ingredients_server.php -->
        <br><br>

        <div id="fridgeFoods">
            <details open>
                <summary>🥩 肉・魚類</summary>
                <div class="food-row">
                    <span>豚ひき肉 (200g)</span>
                    <div>
                        <button class="move-btn" onclick="moveToFreezer(this)">冷凍庫へ</button>
                        <button class="delete-btn" onclick="deleteFood(this)">✖</button>
                    </div>
                </div>
                <div class="food-row">
                    <span>サーモン (2切れ)</span>
                    <div>
                        <button class="move-btn" onclick="moveToFreezer(this)">冷凍庫へ</button>
                        <button class="delete-btn" onclick="deleteFood(this)">✖</button>
                    </div>
                </div>
            </details>

            <details open>
                <summary>🥚 卵・乳製品・大豆</summary>
                <div class="food-row">
                    <span>卵 (6個)</span>
                    <div>
                        <button class="move-btn" onclick="moveToFreezer(this)">冷凍庫へ</button>
                        <button class="delete-btn" onclick="deleteFood(this)">✖</button>
                    </div>
                </div>
            </details>

            <details>
                <summary>🥬 野菜・果物類</summary>
            </details>

            <details>
                <summary>🍚 炭水化物</summary>
            </details>

            <!-- <details>
                <summary>🧂 調味料</summary>
            </details> -->

             <details>
                <summary>🍫 お菓子</summary>
            </details>
        </div>
    </div>
    <br>

    <div class="panel">
        <h2>❄️ 冷凍庫</h2>
        <br>
        <input
            type="text"
            id="freezerSearch"
            placeholder="🔍 食材を検索"
            class="search-box">
        <br><br>

        <div id="freezerFoods">
            <details open>
                <summary>🥩 肉・魚類</summary>
                <div class="food-row">
                    <span>豚ひき肉 (300g)</span>
                    <div>
                        <button class="move-btn" onclick="moveToFridge(this)">冷蔵庫へ</button>
                        <button class="delete-btn" onclick="deleteFood(this)">✖</button>
                    </div>
                </div>
            </details>

            <details open>
                <summary>🥬 野菜・果物類</summary>
                <div class="food-row">
                    <span>ぶなしめじ (200g)</span>
                    <div>
                        <button class="move-btn" onclick="moveToFridge(this)">冷蔵庫へ</button>
                        <button class="delete-btn" onclick="deleteFood(this)">✖</button>
                    </div>
                </div>
            </details>
        </div>
    </div>
    <br>

    <div class="grid">
        <a href="selection.php" class="action-btn">🤖 料理を考える</a>
        <a href="#" class="action-btn secondary">🍳 自分で料理を決める</a>
    </div>

    <button class="floating-add">＋</button>
</div>

<?php include("template/footer.php"); ?>