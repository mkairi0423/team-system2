<?php
 
//search_recipe.php
// 💡 共通の定義・DB接続ファイルを読み込む（必要に応じてパスを調整してください）
// client/page/ の階層から見て、1つ上の client/ の外に出て helpers や database 内にある場合
// require_once __DIR__ . "/../../helpers/db.php";
 
// もしログインチェックなどをここで行う場合は、チームの共通関数（login()など）を呼びします
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>自分で料理を決める（在庫チェック）</title>
   
    <link rel="stylesheet" href="../css/search_recipe.css">
</head>
<body>
 
<?php // include __DIR__ . '/template/header.php'; ?>
 
<div class="search-container">
    <h2>🔍 自分で料理を決める</h2>
    <p class="search-sub">作りたい料理名を入力すると、AIが今の在庫から必要な食材をピックアップします。</p>
 
    <div class="search-box">
        <input type="text" id="recipeSearchInput" placeholder="例：カレー、オムライス、肉じゃが">
        <button id="recipeSearchBtn">検索</button>
    </div>
 
    <div id="resultSection" class="result-section" style="display: none;">
        <p class="result-guide">使う食材にチェックを入れたまま【調理開始】を押してください。</p>
       
        <form id="ingredientCheckForm">
            <div id="checkboxContainer"></div>
        </form>
 
        <div class="action-btns">
            <button id="manualAddBtn" class="btn-add" type="button">➕ リストにない食材を自分の在庫から追加</button>
            <button id="startCookingBtn" class="btn-submit" type="button">🍳 この食材で料理開始！</button>
        </div>
    </div>
</div>
<script src="../js/search_recipe.js"></script>
 
</body>
</html>