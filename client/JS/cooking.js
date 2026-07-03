document.addEventListener("DOMContentLoaded", () => {
    const userIdEl = document.getElementById("current-user-id");
    const userId = userIdEl ? userIdEl.value : null;
    const ingredientsListContainer = document.getElementById("ingredients-target-list");
    const btnComplete = document.getElementById("btn-cooking-complete");
    const API_URL = '../../server/page/cooking_server.php';

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
            startCooking(JSON.parse(storedData));
            localStorage.removeItem("cooking_items");
        } catch (e) { refreshList(); }
    } else {
        refreshList();
    }

    // --- 各種機能 ---
    async function startCooking(items = []) {
        const result = await apiRequest('start', { items: items });
        if (result.success) renderIngredients(result.data);
        else alert("開始エラー: " + result.message);
    }

    async function refreshList() {
        const result = await apiRequest('start', { items: [] });
        if (result.success) renderIngredients(result.data);
    }

    // ★修正：destination（場所）を引数に追加
    async function returnItem(cookingId, destination) {
        const result = await apiRequest('return_item', { 
            cooking_id: cookingId, 
            destination: destination 
        });
        if (result.success) {
            alert("在庫に戻しました");
            refreshList();
        } else alert("エラー: " + result.message);
    }

    // --- 描画ロジック ---
 // ... (前略)

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
        // ★修正：item.quantity は調理中の分量なのでこれをそのまま表示
        div.innerHTML = `
            <span><strong>${item.food}</strong> (${item.quantity}${item.unit})</span>
            <select id="dest-${item.id}">
                <option value="original">元の場所に戻す</option>
                <option value="1">冷蔵庫</option>
                <option value="2">冷凍庫</option>
            </select>
            <button class="btn-return" data-id="${item.id}">戻す</button>
        `;
        ingredientsListContainer.appendChild(div);
    });

    document.querySelectorAll(".btn-return").forEach(btn => {
        btn.addEventListener("click", (e) => {
            const id = e.target.dataset.id;
            const dest = document.getElementById(`dest-${id}`).value;
            if (confirm("在庫に戻しますか？")) returnItem(id, dest);
        });
    });
}
    // --- 料理完了処理 ---
    if (btnComplete) {
        btnComplete.addEventListener("click", async () => {
            if (!confirm("料理を完了しますか？")) return;
            const result = await apiRequest('complete');
            if (result.success) {
                alert("在庫を更新しました！");
                refreshList();
            } else alert("完了エラー: " + result.message);
        });
    }

    window.addEventListener('pageshow', (event) => {
        if (event.persisted) window.location.reload();
    });
});