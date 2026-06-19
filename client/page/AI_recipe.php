<?php
//AIがレシピを考える画面
//条件を入力する専用画面
//AI_recipe.php

$title = "レシピ条件の選択";
$page = "AI_recipe";

include "template/header.php";
include "template/sidebar.php";

?>

<div class="container">
    <div class="header">
        <button type="button" class="btn-home" onclick="location.href='search_recipe.php'">🔍 自分で検索</button>
        <button type="button" class="btn-mypage" onclick="location.href='home.php'">👤 ホーム</button>
    </div>

    <p style="color: #666; margin-top: 15px;">💡 今日の気分に合わせて条件を選んでください。</p>

    <div class="question-block">
        <div class="question-title">Q1. どのご飯（食種）にしますか？</div>
        <div class="grid-options" data-group="meal_type">
            <button type="button" class="option-btn" data-value="朝ごはん">☀️ 朝ごはん</button>
            <button type="button" class="option-btn" data-value="昼ごはん">☁️ 昼ごはん</button>
            <button type="button" class="option-btn selected" data-value="夜ごはん">✨ 夜ごはん</button>
            <button type="button" class="option-btn" data-value="おやつ">🍰 おやつ</button>
        </div>
    </div>

    <div class="question-block">
        <div class="question-title">Q2. 料理のジャンルを選んでください</div>
        <div class="grid-options" data-group="cuisine">
            <button type="button" class="option-btn" data-value="和食">🍙 和食</button>
            <button type="button" class="option-btn" data-value="洋食">🍝 洋食</button>
            <button type="button" class="option-btn selected" data-value="中華">🇨🇳 中華</button>
            <button type="button" class="option-btn" data-value="ヘルシー">🥗 ヘルシー</button>
        </div>
    </div>

    <div class="question-block">
        <div class="question-title">Q3. 味付けの系統を選んでください</div>
        <div class="grid-options" data-group="flavor">
            <button type="button" class="option-btn selected" data-value="辛い系">🔥 辛い系</button>
            <button type="button" class="option-btn" data-value="甘い系">🍯 甘い系</button>
            <button type="button" class="option-btn" data-value="さっぱり">🍋 さっぱり</button>
            <button type="button" class="option-btn" data-value="マイルド">🥦 マイルド</button>
        </div>
    </div>

    <div class="question-block">
        <div class="question-title">Q4. 調理にかける時間は？</div>
        <div class="grid-options two-cols" data-group="cooking_time">
            <button type="button" class="option-btn selected" data-value="15分以内">⏱️ 15分以内で時短</button>
            <button type="button" class="option-btn" data-value="時間がかかっても良い">🍳 時間がかかっても良い</button>
        </div>
    </div>

    <div class="question-block">
        <div class="question-title">Q5. 何人前作りますか？</div>
        <div class="grid-options three-cols" data-group="servings">
            <button type="button" class="option-btn" data-value="1人前">🧍 1人前</button>
            <button type="button" class="option-btn selected" data-value="2人前">👥 2人前</button>
            <button type="button" class="option-btn" data-value="3〜4人前">👨‍👩‍👦 3〜4人前</button>
        </div>
    </div>

    <div class="submit-container">
        <button type="button" class="btn-submit" id="submit-to-ai">🚀 この条件でAIシェフにレシピを聞く！</button>
    </div>
</div>

<?php include "template/footer.php"; ?>

