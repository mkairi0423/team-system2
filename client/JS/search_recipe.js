//自分でレシピ来を検索

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
            if (input && !input.disabled) {
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

            container.innerHTML = ingredientData.map(item => {
                const qty = parseFloat(item.quantity) || 0;
                
                // 💡 在庫が0以下の場合は絶対に選択させないためのフラグ
                const isDisabled = qty <= 0;
                const checkedAttr = (!isDisabled && item.in_stock) ? "checked" : "";
                const disabledAttr = isDisabled ? "disabled" : "";
                
                // 💡 スタイルでクリック自体を完全に無効化する (pointer-events: none)
                const rowStyle = isDisabled 
                    ? "margin: 15px 0; border-bottom: 1px solid #eee; opacity: 0.5; pointer-events: none;" 
                    : "margin: 15px 0; border-bottom: 1px solid #eee;";

                return `
                    <div class="ingredient-row" style="${rowStyle}">
                        <input type="checkbox" name="ingredients[]" value="${item.id}" ${checkedAttr} ${disabledAttr} ${isDisabled ? 'checked="false"' : ''}>
                        <label><strong>${item.food_name}</strong> (在庫: ${item.quantity}${item.unit})</label>
                        <input type="number" name="qty_val[]" value="${item.quantity}" max="${item.quantity}" step="0.1" oninput="validateQty(this)" ${disabledAttr}>
                        <span>${item.unit}</span>
                        <button type="button" class="ratio-btn" data-ratio="1" ${disabledAttr}>MAX</button>
                        <button type="button" class="ratio-btn" data-ratio="0.5" ${disabledAttr}>半分</button>
                        <button type="button" class="ratio-btn" data-ratio="0.33" ${disabledAttr}>1/3</button>
                    </div>
                `;
            }).join('');

            // 💡 さらに念のため、生成された要素の中から在庫0のチェックボックスの .checked を強制的に false に書き換える
            container.querySelectorAll('.ingredient-row').forEach(row => {
                const checkbox = row.querySelector('input[name="ingredients[]"]');
                const qtyInput = row.querySelector('input[name="qty_val[]"]');
                if (qtyInput && parseFloat(qtyInput.value) <= 0) {
                    if (checkbox) {
                        checkbox.checked = false;
                        checkbox.disabled = true;
                    }
                }
            });

        } catch (err) {
            container.innerHTML = `<p style="color:red;">エラー: ${err.message}</p>`;
        }
    });

    // --- 【重要】料理開始ボタン：他システム連携用データの構築 ---
if (startCookingBtn) {
        startCookingBtn.addEventListener("click", () => {
            const checkedInputs = document.querySelectorAll('input[name="ingredients[]"]:checked');
            if (checkedInputs.length === 0) return alert("食材を選択してください");

            const searchKeyword = keywordInput ? keywordInput.value.trim() : '手作り料理';

            const selectedItems = Array.from(checkedInputs).map(input => {
                const row = input.closest(".ingredient-row");
                const qtyInput = row.querySelector('input[name="qty_val[]"]');
                return {
                    id: input.value,
                    food_name: row.querySelector('label strong').textContent,
                    quantity: parseFloat(qtyInput.value) || 0,
                    unit: row.querySelector('span').textContent.trim()
                };
            });

            localStorage.setItem("cooking_items", JSON.stringify({
                dish_name: searchKeyword,
                items: selectedItems
            }));
            
            // 💡 修正：URLのクエリパラメータに料理名（キーワード）を付けて渡す
            window.location.href = `cooking.php?dish=${encodeURIComponent(searchKeyword)}`;
        });
    }
});