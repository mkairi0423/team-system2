document.addEventListener("DOMContentLoaded", () => {
    const searchBtn = document.getElementById("recipeSearchBtn");
    const container = document.getElementById("checkboxContainer");
    const resultSection = document.getElementById("resultSection");

    if (!searchBtn) return;

    searchBtn.addEventListener("click", async () => {
        const keyword = document.getElementById("recipeSearchInput").value.trim();
        if (!keyword) { alert("キーワードを入力してください"); return; }

        container.innerHTML = "検索中...";
        resultSection.style.display = "block";

        try {
            // サーバーへリクエスト
            const response = await fetch(`../../server/page/get_recipe_ingredients.php?user_id=1&keyword=${encodeURIComponent(keyword)}`);
            const data = await response.json();

            if (!data.success) throw new Error(data.message);

            let html = `<p>材料と使用量を選んでください:</p>`;
            
            // PHPから送られてくるキー名 (id, food_name, quantity, unit) に完全に一致させています
            data.ingredients.forEach(item => {
                const itemId = item.id; 
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
                        </div>
                    </div>`;
            });
            container.innerHTML = html;
        } catch (err) {
            container.innerHTML = `<p style="color:red;">エラー: ${err.message}</p>`;
        }
    });
});