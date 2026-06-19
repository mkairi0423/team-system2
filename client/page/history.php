<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>料理履歴一覧</title>
    <style>
        .history-container { max-width: 600px; margin: 0 auto; padding: 20px; font-family: sans-serif; }
        .history-card { background: #fff; border: 1px solid #ddd; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .history-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #f4f4f4; padding-bottom: 10px; margin-bottom: 15px; }
        .dish-name { margin: 0; font-size: 1.3rem; color: #333; }
        .cooked-date { font-size: 0.85rem; color: #888; }
        .ingredient-list { list-style: none; padding: 0; margin: 0; }
        .ingredient-item { padding: 6px 0; font-size: 0.95rem; color: #555; border-bottom: 1px dashed #eee; }
        .ingredient-item:last-child { border-bottom: none; }
        .no-history { text-align: center; color: #999; padding: 40px; }
    </style>
</head>
<body>

    <div class="history-container">
        <h2>🍳 これまでの料理履歴</h2>
        <hr>
        <div id="history-list-container">
            <p style="text-align:center; color:#999;">⏳ 履歴を読み込み中...</p>
        </div>
    </div>

    <script src="../js/history.js"></script>
</body>
</html>