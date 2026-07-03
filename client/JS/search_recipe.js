
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

    // --- 検索処理（既存の処理） ---
    searchBtn.addEventListener("click", async () => {
        const keyword = keywordInput.value.trim();
        if (!keyword) return alert("キーワードを入力してください");

        container.innerHTML = "検索中...";
        resultSection.style.display = "block";

        try {
            const response = await fetch(`../../server/page/get_recipe_ingredients.php?user_id=1&keyword=${encodeURIComponent(keyword)}`);
            let ingredientData = [];

            if (response.status === 429) {
                ingredientData = [
                    { id: 991, food_name: "テストキャベツ", quantity: 1, unit: "個", in_stock: true },
                    { id: 992, food_name: "テスト豚ひき肉", quantity: 200, unit: "g", in_stock: true }
                ];
            } else {
                const data = await response.json();
                if (!data.success) throw new Error(data.message);
                ingredientData = data.ingredients;
            }

            container.innerHTML = ingredientData.map(item => `
                <div class="ingredient-row" style="margin: 15px 0; border-bottom: 1px solid #eee;">
                    <input type="checkbox" name="ingredients[]" value="${item.id}" ${item.in_stock ? "checked" : ""}>
                    <label><strong>${item.food_name}</strong> (在庫: ${item.quantity}${item.unit})</label>
                    <input type="number" name="qty_val[]" value="${item.quantity}" max="${item.quantity}" step="0.1" oninput="validateQty(this)">
                    <span>${item.unit}</span>
                    <button type="button" class="ratio-btn" data-ratio="1">MAX</button>
                    <button type="button" class="ratio-btn" data-ratio="0.5">半分</button>
                    <button type="button" class="ratio-btn" data-ratio="0.33">1/3</button>
                </div>
            `).join('');
        } catch (err) {
            container.innerHTML = `<p style="color:red;">エラー: ${err.message}</p>`;
        }
    });

    // --- 【重要】料理開始ボタン：他システム連携用データの構築 ---
    if (startCookingBtn) {
        startCookingBtn.addEventListener("click", () => {
            const checkedInputs = document.querySelectorAll('input[name="ingredients[]"]:checked');
            if (checkedInputs.length === 0) return alert("食材を選択してください");

            const selectedItems = Array.from(checkedInputs).map(input => {
                const row = input.closest(".ingredient-row");
                const qtyInput = row.querySelector('input[name="qty_val[]"]');
                return {
                    id: input.value,
                    // food_nameは強固に取得（他のシステムがIDでなく名前を参照する場合のため）
                    food_name: row.querySelector('label strong').textContent,
                    quantity: parseFloat(qtyInput.value) || 0,
                    unit: row.querySelector('span').textContent.trim()
                };
            });

            // システム全体で参照するキー名: cooking_items
            localStorage.setItem("cooking_items", JSON.stringify(selectedItems));
            window.location.href = "cooking.php";
        });
    }
});