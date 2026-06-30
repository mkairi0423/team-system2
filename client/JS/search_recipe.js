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

    // --- 分量調整ボタンのイベント（イベント委譲） ---
    container.addEventListener("click", (e) => {
        const btn = e.target.closest(".ratio-btn");
        if (btn) {
            const ratio = parseFloat(btn.dataset.ratio);
            const row = btn.closest(".ingredient-row");
            const input = row.querySelector('input[name="qty_val[]"]');

            if (input) {
                const max = parseFloat(input.getAttribute('max')) || 0;
                // 計算結果を小数点第1位程度に整形
                const calculated = (max * ratio).toFixed(2).replace(/\.?0+$/, "");
                input.value = calculated;
            }
        }
    });  

    // --- 検索処理 ---
    searchBtn.addEventListener("click", async () => {
        console.log("検索ボタンが押されました"); // ★追加
        const keyword = keywordInput.value.trim();

        if (!keyword) { alert("キーワードを入力してください"); return; }
        console.log("キーワード:", keyword); 
        container.innerHTML = "検索中...";
        resultSection.style.display = "block";

        let ingredientData = [];

        try {
            const url = `../../server/page/get_recipe_ingredients.php?user_id=1&keyword=${encodeURIComponent(keyword)}`;
            const response = await fetch(url);

            // 429エラー時のテストデータ分岐
            if (response.status === 429) {
                console.warn("API制限(429)を検知。テストデータを使用します。");
                ingredientData = [
                    { id: 991, food_name: "テストキャベツ", quantity: 1, unit: "個", in_stock: true },
                    { id: 992, food_name: "テスト豚ひき肉", quantity: 200, unit: "g", in_stock: true },
                    { id: 993, food_name: "テストたまご", quantity: 3, unit: "個", in_stock: true },
                    { id: 994, food_name: "テスト玉ねぎ", quantity: 2, unit: "個", in_stock: true }
                ];
            } else if (!response.ok) {
                throw new Error("通信エラー: " + response.status);
            } else {
                const text = await response.text();
                const data = JSON.parse(text);
                if (!data.success) throw new Error(data.message || "データ取得に失敗しました");
                ingredientData = data.ingredients;
            }

            // HTML生成
            let html = `<p>材料と使用量を選んでください:</p>`;
            ingredientData.forEach(item => {
                const max = parseFloat(item.quantity) || 0;
                const unit = item.unit || "";
                const disabledAttr = item.in_stock ? "" : "disabled";

                html += `
                <div style="margin: 15px 0; border-bottom: 1px solid #eee; padding-bottom: 10px;" class="ingredient-row">
                    <input type="checkbox" name="ingredients[]" value="${item.id}" ${item.in_stock ? "checked" : ""} ${disabledAttr}>
                    <label><strong>${item.food_name}</strong> (在庫: ${max}${unit})</label>
                    <div style="margin-top: 5px; margin-left: 20px;">
                        <input type="number" name="qty_val[]" data-id="${item.id}" value="${max}"
                               max="${max}" step="0.1" style="width: 80px;" oninput="validateQty(this)">
                        <span>${unit}</span>
                        <button type="button" class="ratio-btn" data-ratio="1">MAX</button>
                        <button type="button" class="ratio-btn" data-ratio="0.5">半分</button>
                        <button type="button" class="ratio-btn" data-ratio="0.33">1/3</button>
                    </div>
                </div>`;
            });
            container.innerHTML = html;

        } catch (err) {
            console.error(err);
            container.innerHTML = `<p style="color:red;">エラー: ${err.message}</p>`;
        }
    });

    // --- 「料理開始！」ボタンの処理 ---
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
                const row = input.closest(".ingredient-row");
                const qtyInput = row.querySelector('input[name="qty_val[]"]');
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