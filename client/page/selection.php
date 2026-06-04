<?php
//条件を選択する画面　
//selection.php
//条件検索をしてその結果のレシピを表示する画面



?>


<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="../css/selection_AI_recipe.css">
</head>
<body>

    <form action="TODO" method="POST">
        
    </form>
    

<div class="container">
    <div class="header">
       <button type="button" class="btn-home" onclick="window.location.href='AI_recipe.php'">← 条件を選び直す</button>
        <h2 style="margin: 0; font-size: 1.3rem;">AIの提案レシピ</h2>
    </div>
    
    <p style="color: #666; margin-top: 15px;">💡 賞味期限が近い食材、冷凍保存の長いストックを優先した提案です</p>
    
    <div id="recipe-list-container"></div>
</div>

<script src="../JS/selection_AI_recipe.js"></script>
</body>
</html>