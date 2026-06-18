document.addEventListener("DOMContentLoaded", () => {
    const searchBtn = document.getElementById("recipeSearchBtn");
    const keywordInput = document.getElementById("recipeSearchInput");
    const container = document.getElementById("checkboxContainer");
    const resultSection = document.getElementById("resultSection");

    if (!searchBtn) return;

    searchBtn.addEventListener("click", async () => {
        const keyword = keywordInput.value.trim();
        if (!keyword) { alert("キーワードを入力してください"); return; }

        container.innerHTML = "検索中...";
        resultSection.style.display = "block";

        try {
            // PHPへリクエスト
            const url = `../../server/page/get_recipe_ingredients.php?user_id=1&keyword=${encodeURIComponent(keyword)}`;
            const response = await fetch(url);
            const data = await response.json();

            if (!data.success) {
                throw new Error(data.message || "データ取得に失敗しました");
            }

            // HTML生成ロジック：在庫連動チェックボックス
            let html = `<p>材料を選んでください:</p>`;
            data.ingredients.forEach(item => {
                // 在庫がない場合はチェック不可（disabled）、ある場合はチェック済み（checked）
                const disabledAttr = item.in_stock ? "" : "disabled";
                const checkedAttr = item.in_stock ? "checked" : "";

                html += `
                    <div style="margin: 8px 0;">
                        <input type="checkbox" id="ing_${item.food}" name="ingredients[]" 
                               value="${item.food}" ${checkedAttr} ${disabledAttr}>
                        <label for="ing_${item.food}" style="${item.in_stock ? "" : "color: #999;"}">
                            ${item.food} ${item.in_stock ? "" : "（在庫なし）"}
                        </label>
                    </div>`;
            });
            
            container.innerHTML = html;

        } catch (err) {
            container.innerHTML = `<p style="color:red;">エラー: ${err.message}</p>`;
            console.error(err);
        }
    });
});