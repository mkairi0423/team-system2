<?php

$title = "食材登録";
$page = "food";

include("template/header.php");
include("template/sidebar.php");

?>

<div class="main">

    <div class="topbar">
        <h1>食材登録</h1>
    </div>


    <div class="grid">

        <!-- カメラ登録 -->

        <div class="card">

            <h3>📷 レシート食材重量逆算スキャナー</h3>

            <br>

            <p>
                レシートの写真を撮影するか、
                画像を選択してください
            </p>

            <br>

            <label for="receipt-file" class="file-label">
                📸 写真を撮る / 画像を選択
            </label>

            <input
                type="file"
                accept="image/*"
                capture="environment"
                id="receipt-file">

            <br><br>

            <button
                class="btn"
                id="scanBtn">

                画像を解析

            </button>

            <br><br>

            <div id="result">
                ここに解析結果が表示されます
            </div>

        </div>

        <!-- 手入力 -->

        <div class="card">

            <h3>⌨️ 手入力登録</h3>

            <br>

            <input
                type="text"
                id="foodName"
                placeholder="食材名"
                class="search-box">

            <br><br>

            <input
                type="number"
                id="foodAmount"
                placeholder="数量"
                class="search-box">

            <br><br>

            <select
                id="foodCategory"
                class="search-box">

                <option>肉・魚類</option>
                <option>卵・乳製品・大豆</option>
                <option>野菜・果物類</option>
                <option>炭水化物</option>
                <option>調味料</option>
                <option>お菓子</option>

            </select>

            <br><br>

            <button
                class="btn"
                onclick="addFood()">

                登録する

            </button>

        </div>

    </div>

    <!-- 登録済み -->

    <div class="panel">

        <h2>📋 登録予定の食材</h2>

        <br>

        <div id="foodList">

            <div class="food-row">
                <span>卵 (6個)</span>
            </div>

            <div class="food-row">
                <span>牛乳 (1本)</span>
            </div>

        </div>

    </div>

</div>

<?php include("template/footer.php"); ?>

<script src="../js/food.js"></script>