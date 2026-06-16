<?php
// 調理中画面のクライアント画面
//提案使う
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>調理中画面</title>
    <link rel="stylesheet" href="../../css/style.css"> 
    <link rel="stylesheet" href="cooking.css">
</head>
<body>

<input type="hidden" id="current-user-id" value="1">

<div class="cooking-container">
    <h2 id="cooking-recipe-name">🍳 料理中...</h2>
    <p>使用する食材の一覧です。入れ忘れた食材は「戻す」を押してください。</p>
    
    <hr>
    
    <div id="ingredients-target-list"></div>
    
    <hr>
    
    <button type="button" id="btn-cooking-complete" class="btn-complete">🍳 料理完了！</button>
</div>

<script src="cooking.js"></script> 
</body>
</html>