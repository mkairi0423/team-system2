// history.js
document.addEventListener('DOMContentLoaded', () => {
    loadCookingHistory();
});

async function loadCookingHistory() {
    const container = document.getElementById('history-list-container');
    const userId = 1; // 実際の環境に合わせてセッション等から取得してください

    try {
        // 💡 履歴取得APIを叩く（※PHP側で action=get_history を用意する想定）
        const response = await fetch(`../../server/page/cooking_server.php?action=get_history&user_id=${userId}`);
        if (!response.ok) throw new Error('履歴の取得に失敗しました。');

        const result = await response.json();
        if (!result.success) throw new Error(result.message);

        const rawHistory = result.data; // サーバーから届いた生データ（配列）

        if (!rawHistory || rawHistory.length === 0) {
            container.innerHTML = '<p class="no-history">まだ料理履歴がありません。最初の料理を作ってみましょう！</p>';
            return;
        }

        // 🔥 【重要】バラバラの行データを「料理名＋日時」ごとにグループ化する
        const groupedHistory = [];

        rawHistory.forEach(row => {
            // 「料理名」と「作った日時」を組み合わせた独自のキーを作る（一意にするため）
            const groupKey = `${row.dish_name}_${row.cooked_at}`;
            
            // すでにグループが存在するかチェック
            let group = groupedHistory.find(g => g.key === groupKey);

            if (!group) {
                // まだ無ければ、新しい料理グループの土台を作る
                group = {
                    key: groupKey,
                    dish_name: row.dish_name,
                    cooked_at: row.cooked_at,
                    ingredients: [] // ここに使った食材を詰め込んでいく
                };
                groupedHistory.push(group);
            }

            // 食材データがあれば、グループの食材リストに追加
            if (row.used_food_name) {
                group.ingredients.push({
                    name: row.used_food_name,
                    quantity: row.quantity,
                    unit: row.unit
                });
            }
        });

        // 👑 グループ化したデータを元に、HTMLカードを生成して画面に表示！
        container.innerHTML = ''; // ローディング表示を消す

        groupedHistory.forEach(recipe => {
            // 食材リストのHTMLを組み立てる
            const ingredientItemsHTML = recipe.ingredients.map(ing => {
                const qtyText = ing.quantity !== null ? `(${ing.quantity}${ing.unit})` : '';
                return `<li class="ingredient-item">🔸 ${ing.name}${qtyText}</li>`;
            }).join('');

            // 日時のフォーマットを綺麗に（2026/06/19 14:44）
            const formattedDate = recipe.cooked_at.replace(/-/g, '/').substring(0, 16);

            // カードの作成
            const card = document.createElement('div');
            card.className = 'history-card'; // CSSと連動
            card.innerHTML = `
                <div class="history-header">
                    <h3 class="dish-name">料理：${recipe.dish_name}</h3>
                    <span class="cooked-date">📅 ${formattedDate}</span>
                </div>
                <h4 class="ingredient-title">使った食材：</h4>
                <ul class="ingredient-list">
                    ${ingredientItemsHTML}
                </ul>
            `;
            container.appendChild(card);
        });

    } catch (err) {
        container.innerHTML = `<p style="text-align:center; color:red;">エラー: ${err.message}</p>`;
    }
}