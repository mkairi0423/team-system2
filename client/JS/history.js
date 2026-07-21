// ../js/history.js
document.addEventListener('DOMContentLoaded', () => {
    loadCookingHistory();
});

async function loadCookingHistory() {
    const container = document.getElementById('history-list-container');

    if (!container) {
        return;
    }
    
    // TODO:userIdをセッションからとってくる
    const userId = 1; //実際の環境に合わせてセッション等から取得してください

    try {
        // 💡 履歴取得APIを叩く
        const response = await fetch(`../../server/page/cooking_server.php?action=get_history&user_id=${userId}`);
        if (!response.ok) throw new Error('履歴の取得に失敗しました。');

        const result = await response.json();
        if (!result.success) throw new Error(result.message);

        const rawHistory = result.data; // サーバーから届いた生データ

        if (!rawHistory || rawHistory.length === 0) {
            container.innerHTML = '<p class="no-history" style="text-align:center; padding:40px; color:#64748b;">まだ料理履歴がありません。最初の料理を作ってみましょう！</p>';
            return;
        }

        // バラバラの行データを「料理名＋日時」ごとにグループ化
        const groupedHistory = [];
        rawHistory.forEach(row => {
            const groupKey = `${row.dish_name}_${row.cooked_at}`;
            let group = groupedHistory.find(g => g.key === groupKey);

            if (!group) {
                group = {
                    key: groupKey,
                    dish_name: row.dish_name,
                    cooked_at: row.cooked_at,
                    ingredients: []
                };
                groupedHistory.push(group);
            }

            if (row.used_food_name) {
                group.ingredients.push({
                    name: row.used_food_name,
                    quantity: row.quantity,
                    unit: row.unit
                });
            }
        });

        // 👑 既存のCSSクラス（card, food, badge）をフル活用して組み立てる！
        container.innerHTML = ''; 

        groupedHistory.forEach(recipe => {
            // 食材リスト（.food と .badge クラスを流用。ul/liは使いません）
            const ingredientItemsHTML = recipe.ingredients.map(ing => {
                const qtyText = ing.quantity !== null ? `(${ing.quantity}${ing.unit})` : '';
                return `
                    <div class="food" style="margin-top: 8px; background: #f8fafc; padding: 12px; border-radius: 12px; display: flex; justify-content: space-between; align-items: center;">
                        <span>🔸 ${ing.name}</span>
                        <span class="badge" style="background: #e2e8f0; color: #475569; padding: 4px 8px; border-radius: 6px; font-size: 0.85rem;">${qtyText}</span>
                    </div>
                `;
            }).join('');

            // 日時のフォーマットを綺麗に
            const formattedDate = recipe.cooked_at.replace(/-/g, '/').substring(0, 16);

            // 全体を .card クラスで包む
            const card = document.createElement('div');
            card.className = 'card'; // 👈 これで自動的にホーム画面と同じ白枠カードになります
            card.style.marginBottom = '25px';
            card.style.padding = '25px';
            card.style.textAlign = 'left'; // 左寄せを保証
            
            card.innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #f1f5f9; padding-bottom: 12px; margin-bottom: 15px;">
                    <h2 style="margin: 0; font-size: 1.3rem; color: #1e293b;">🍳 ${recipe.dish_name}</h2>
                    <span style="font-size: 0.9rem; color: #64748b; font-weight: 500;">📅 ${formattedDate}</span>
                </div>
                <h3 style="font-size: 0.95rem; color: #475569; margin: 0 0 10px 0; font-weight: 600;">使った食材：</h3>
                <div class="ingredient-box">
                    ${ingredientItemsHTML}
                </div>
            `;
            container.appendChild(card);
        });

    } catch (err) {
        container.innerHTML = `<p style="text-align:center; color:red; padding:20px;">エラー: ${err.message}</p>`;
    }
}