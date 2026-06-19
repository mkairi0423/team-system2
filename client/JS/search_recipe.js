document.addEventListener("DOMContentLoaded", () => {
    const searchBtn = document.getElementById("recipeSearchBtn");
    const keywordInput = document.getElementById("recipeSearchInput");
    const container = document.getElementById("checkboxContainer");
    const resultSection = document.getElementById("resultSection");
    const startCookingBtn = document.getElementById("startCookingBtn");

    if (!searchBtn) return;

    // --- ヘルパー関数: 割合適用 ---
    window.applyRatio = (id, ratio) => {
        const input = document.querySelector(`input[name="qty_val[]"][data-id="${id}"]`);
        if (!input) return;
        const max = parseFloat(input.getAttribute('max')) || 0;
        input.value = (max * ratio).toFixed(2).replace(/\.?0+$/, "");
    };

    // --- ヘルパー関数: 上限チェック ---
    window.validateQty = (input) => {
        const max = parseFloat(input.getAttribute('max')) || 0;
        let val = parseFloat(input.value) || 0;
        if (val > max) input.value = max;
        if (val < 0) input.value = 0;
    };

    // 検索処理
    searchBtn.addEventListener("click", async () => {
        const keyword = keywordInput.value.trim();
        if (!keyword) { alert("キーワードを入力してください"); return; }

        container.innerHTML = "検索中...";
        resultSection.style.display = "block";

        try {
            const url = `../../server/page/get_recipe_ingredients.php?user_id=1&keyword=${encodeURIComponent(keyword)}`;
            const response = await fetch(url);
            const data = await response.json();

            if (!data.success) throw new Error(data.message || "データ取得に失敗しました");

            let html = `<p>材料と使用量を選んでください:</p>`;
            
            data.ingredients.forEach(item => {
                // データベース定義に合わせてカラム名を修正
                // IDは item.ingredient_id、食材名は item.food_name を使用
                const itemId = item.ingredient_id;
                const foodName = item.food_name;
                const max = parseFloat(item.quantity) || 0;
                const unit = item.unit || "";
                
                html += `
                    <div style="margin: 15px 0; border-bottom: 1px solid #eee; padding-bottom: 10px;">
                        <input type="checkbox" name="ingredients[]" value="${itemId}" ${item.in_stock ? "checked" : ""}>
                        <label><strong>${foodName}</strong> (在庫: ${max}${unit})</label>
                        <div style="margin-top: 5px; margin-left: 20px;">
                            <input type="number" name="qty_val[]" data-id="${itemId}" value="${max}" 
                                   max="${max}" step="0.1" style="width: 80px;" oninput="validateQty(this)">
                            <span>${unit}</span>
                            <button type="button" onclick="applyRatio(${itemId}, 1)">MAX</button>
                            <button type="button" onclick="applyRatio(${itemId}, 0.5)">半分</button>
                            <button type="button" onclick="applyRatio(${itemId}, 0.33)">1/3</button>
                        </div>
                    </div>`;
            });
            container.innerHTML = html;
        } catch (err) {
            container.innerHTML = `<p style="color:red;">エラー: ${err.message}</p>`;
        }
    });

    // 「料理開始！」ボタンの処理
    if (startCookingBtn) {
        startCookingBtn.addEventListener("click", () => {
            const checkedInputs = document.querySelectorAll('input[name="ingredients[]"]:checked');
            if (checkedInputs.length === 0) {
                alert("少なくとも1つの食材を選択してください");
                return;
            }

            const selectedItems = [];
            checkedInputs.forEach(input => {
                const id = input.value;
                const qtyInput = document.querySelector(`input[name="qty_val[]"][data-id="${id}"]`);
                selectedItems.push({
                    id: id,
                    quantity: parseFloat(qtyInput.value) || 0
                });
            });

            localStorage.setItem("cooking_items", JSON.stringify(selectedItems));
            window.location.href = "cooking.php";
        });
    }
});