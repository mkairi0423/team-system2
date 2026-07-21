document.addEventListener("DOMContentLoaded", () => {
    const userIdEl = document.getElementById("current-user-id");
    const userId = userIdEl ? userIdEl.value : null;
    const ingredientsListContainer = document.getElementById("ingredients-target-list");
    const btnComplete = document.getElementById("btn-cooking-complete");
    const API_URL = '../../server/page/cooking_server.php';

    // 💡 URLのクエリパラメータから料理名を取得（フォールバック付き）
    const urlParams = new URLSearchParams(window.location.search);
    const currentDishName = urlParams.get('dish') || '手作り料理';

    async function apiRequest(action, payload = {}) {
        const response = await fetch(`${API_URL}?action=${action}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: userId, ...payload })
        });
        const rawText = await response.text();
        try {
            return JSON.parse(rawText);
        } catch (e) {
            console.error("JSON解析失敗:", rawText);
            throw new Error("サーバーから不正なデータが返されました");
        }
    }

    // --- 起動時処理 ---
    const storedData = localStorage.getItem("cooking_items");
    if (storedData) {
        try {
            const parsedData = JSON.parse(storedData);
            
            // 💡 構造が変わったため、オブジェクト内から items を取り出す
            let items = [];
            if (Array.isArray(parsedData)) {
                items = parsedData;
            } else if (parsedData && parsedData.items) {
                items = parsedData.items;
            }

            startCooking(items);
            localStorage.removeItem("cooking_items");
        } catch (e) { 
            console.error("データ解析エラー:", e);
            refreshList(); 
        }
    } else {
        refreshList();
    }

    async function startCooking(items = []) {
        const result = await apiRequest('start', { items: items });
        if (result.success) {
            refreshList();
        } else alert("開始エラー: " + result.message);
    }

    async function refreshList() {
        const result = await apiRequest('get_list'); 
        if (result.success) renderIngredients(result.data);
    }

    // 戻す処理
    async function returnItem(cookingId) {
        const result = await apiRequest('return_item', { 
            cooking_id: cookingId
        });
        if (result.success) {
            alert("元の場所に戻しました");
            refreshList();
        } else alert("エラー: " + result.message);
    }

    // --- 描画ロジック ---
    function renderIngredients(items) {
        if (!ingredientsListContainer) return;
        ingredientsListContainer.innerHTML = "";
        
        if (!items || items.length === 0) {
            ingredientsListContainer.innerHTML = "<p>調理中の食材はありません。</p>";
            if (btnComplete) btnComplete.disabled = true;
            return;
        }

        if (btnComplete) btnComplete.disabled = false;

        items.forEach(item => {
            const div = document.createElement("div");
            div.className = "ingredient-item";
            div.innerHTML = `
                <span><strong>${item.food}</strong> (${item.quantity}${item.unit})</span>
                <button class="btn-return" data-id="${item.id}">元の場所に戻す</button>
            `;
            ingredientsListContainer.appendChild(div);
        });

        document.querySelectorAll(".btn-return").forEach(btn => {
            btn.addEventListener("click", (e) => {
                const id = e.target.dataset.id;
                if (confirm("元の場所に戻しますか？")) returnItem(id);
            });
        });
    }

    // --- 料理完了処理 ---
    if (btnComplete) {
        btnComplete.addEventListener("click", async () => {
            // 💡 取得した料理名（currentDishName）をサーバーへ送信する
            const result = await apiRequest('complete', { dish_name: currentDishName });
            if (result.success) {
                alert("調理完了！履歴に保存しました。");
                // 完了後に履歴画面等へ遷移する場合はここに window.location.href を追加してください
                refreshList();
            } else {
                alert("完了エラー: " + result.message);
            }
        });
    }

    window.addEventListener('pageshow', (event) => {
        if (event.persisted) window.location.reload();
    });
});