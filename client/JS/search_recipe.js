
//自分でレシピを検索


document.addEventListener("DOMContentLoaded", () => {
    const searchBtn = document.getElementById("recipeSearchBtn");
    const keywordInput = document.getElementById("recipeSearchInput");
    const container = document.getElementById("checkboxContainer");
    const resultSection = document.getElementById("resultSection");
    const startCookingBtn = document.getElementById("startCookingBtn");

    if (!searchBtn) return;

    // --- 在庫数値のバリデーション ---
    window.validateQty = (input) => {
        const max = parseFloat(input.getAttribute('max')) || 0;
        let val = parseFloat(input.value) || 0;
        if (val > max) input.value = max;
        if (val < 0) input.value = 0;
    };

    // --- 分量調整ボタンのイベント委譲 ---
    container.addEventListener("click", (e) => {
        const btn = e.target.closest(".ratio-btn");
        if (btn) {
            const ratio = parseFloat(btn.dataset.ratio);
            const row = btn.closest(".ingredient-row");
            const input = row.querySelector('input[name="qty_val[]"]');
            if (input) {
                const max = parseFloat(input.getAttribute('max')) || 0;
                input.value = (max * ratio).toFixed(2).replace(/\.?0+$/, "");
            }
        }
    });

    // 検索処理を簡略化
    searchBtn.addEventListener("click", async () => {
        const keyword = keywordInput.value.trim();
        if (!keyword) return alert("キーワードを入力してください");

        container.innerHTML = "検索中...";
        resultSection.style.display = "block";

        try {
            const response = await fetch(`../../server/page/get_recipe_ingredients.php?user_id=1&keyword=${encodeURIComponent(keyword)}`);
            const data = await response.json();

            if (!data.success) throw new Error(data.message || "検索に失敗しました");

            const ingredientData = data.ingredients;

            container.innerHTML = ingredientData.map(item => `
            <div class="ingredient-row" style="margin: 15px 0; border-bottom: 1px solid #eee;">
                <input type="checkbox" name="ingredients[]" value="${item.id}" ${item.in_stock ? "checked" : ""}>
                <label><strong>${item.food_name}</strong> (在庫: ${item.quantity}${item.unit})</label>
                <input type="number" name="qty_val[]" value="${item.quantity}" max="${item.quantity}" step="0.1" oninput="validateQty(this)">
                <span>${item.unit}</span>
                <button type="button" class="ratio-btn" data-ratio="1">MAX</button>
                <button type="button" class="ratio-btn" data-ratio="0.5">半分</button>
            </div>
        `).join('');
        } catch (err) {
            container.innerHTML = `<p style="color:red;">エラー: ${err.message}</p>`;
        }
    });

    
    // search_recipe.js 内の startCookingBtn の送信処理
    startCookingBtn.addEventListener("click", async () => {
        const checkedInputs = document.querySelectorAll('input[name="ingredients[]"]:checked');
        if (checkedInputs.length === 0) return alert("食材を選択してください");

        const selectedItems = Array.from(checkedInputs).map(input => {
            const row = input.closest(".ingredient-row");
            const qtyInput = row.querySelector('input[name="qty_val[]"]');

            // 【修正点】ここで「本当に有効なIDかどうか」を確認する
            // もしIDが "null" という文字列や、テスト用の怪しい値なら null に変換して送信する
            let ingId = input.value;
            if (ingId === "null" || ingId === "" || isNaN(ingId)) {
                ingId = null;
            }

            return {
                original_ingredient_id: ingId, // IDが無効なら null になる
                food: row.querySelector('label strong').textContent,
                use_quantity: parseFloat(qtyInput.value) || 0,
                unit: row.querySelector('span').textContent.trim()
            };
        });

        // サーバーへ送信
        const response = await fetch('../../server/page/cooking_server.php?action=start', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                user_id: 1,
                items: selectedItems
            })
        });

        // 💡 このあと、fetch を使って cooking_server.php に送信する処理を追加します
        try {
            const response = await fetch('../../server/page/cooking_server.php?action=start', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    user_id: 1, // ★ログインユーザーIDに書き換えてください
                    items: selectedItems
                })
            });

            const result = await response.json();
            if (result.success) {
                window.location.href = "cooking.php"; // 成功したら遷移
            } else {
                alert("開始エラー: " + result.message);
            }
        } catch (err) {
            alert("通信エラー: " + err.message);
        }
    });
});