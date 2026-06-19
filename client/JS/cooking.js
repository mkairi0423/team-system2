//cooking.js
document.addEventListener("DOMContentLoaded", () => {
    // HTMLから要素を取得
    const userId = document.getElementById("current-user-id").value;
    const ingredientsListContainer = document.getElementById("ingredients-target-list");
    const btnComplete = document.getElementById("btn-cooking-complete");
 
    // バックエンド（API）のパス
    const API_URL = '../../server/page/cooking_server.php';
 
    // 1. 画面起動時に24時間放置データの自動復元を走らせる
    autoRestoreExpiredData();
 
    // 2. 画面起動時の処理（★修正：cooking_items を受け取るように変更）
    const savedItems = localStorage.getItem("cooking_items");
    if (savedItems) {
        const items = JSON.parse(savedItems);
        // localStorageから受け取った {id: X, quantity: Y} の配列をそのままサーバーへ送る
        startCookingWithItems(userId, items);
        // 処理が終わったら消去
        localStorage.removeItem("cooking_items");
    } else {
        refreshCookingList(userId);
    }
 
    // --------------------------------------------------------
    // ① 検索画面からのデータ引き継ぎ処理
    // --------------------------------------------------------
    function startCookingWithItems(uid, items) {
        fetch(`${API_URL}?action=start`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: uid, items: items })
        })
        .then(res => res.json())
        .then(response => {
            if (response.success) {
                renderIngredients(response.data);
            } else {
                alert("調理開始エラー: " + response.message);
                refreshCookingList(uid);
            }
        })
        .catch(error => {
            console.error("通信エラー:", error);
            refreshCookingList(uid);
        });
    }
 
    // --------------------------------------------------------
    // 【重要】調理中リストの最新状態を取得
    // --------------------------------------------------------
    function refreshCookingList(uid) {
        // 現在の調理リストを取得するため、itemsを空で送信する
        fetch(`${API_URL}?action=start`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: uid, items: [] })
        })
        .then(res => res.json())
        .then(response => {
            if (response.success) {
                renderIngredients(response.data);
            } else {
                alert("データ更新エラー: " + response.message);
            }
        })
        .catch(error => console.error("通信エラー:", error));
    }
 
    // --------------------------------------------------------
    // リストの描画処理
    // --------------------------------------------------------
    function renderIngredients(items) {
        ingredientsListContainer.innerHTML = "";
        if (!items || items.length === 0) {
            ingredientsListContainer.innerHTML = "<p>調理中の食材はありません。</p>";
            btnComplete.disabled = true;
            return;
        }
        btnComplete.disabled = false;
        items.forEach(item => {
            const div = document.createElement("div");
            div.className = "ingredient-item";
            div.style.display = "flex";
            div.style.justifyContent = "space-between";
            div.style.alignItems = "center";
            div.style.margin = "10px 0";
            div.innerHTML = `
                <span class="item-info"><strong>${item.food}</strong> (${item.quantity} ${item.unit || ''})</span>
                <div class="item-controls">
                    <select class="select-destination" id="dest-${item.id}">
                        <option value="original">元の場所</option>
                        <option value="fridge">冷蔵庫</option>
                        <option value="freezer">冷凍庫</option>
                    </select>
                    <button type="button" class="btn-return" data-id="${item.id}" data-name="${item.food}">戻す</button>
                </div>
            `;
            ingredientsListContainer.appendChild(div);
        });
        document.querySelectorAll(".btn-return").forEach(button => {
            button.addEventListener("click", (e) => {
                const cookingId = e.target.getAttribute("data-id");
                const foodName = e.target.getAttribute("data-name");
                const destination = document.getElementById(`dest-${cookingId}`).value;
                if (confirm(`「${foodName}」を在庫に戻しますか？`)) {
                    returnItem(cookingId, destination);
                }
            });
        });
    }
 
    // ② 食材の個別差し戻し
    function returnItem(cookingId, destination) {
        fetch(`${API_URL}?action=return_item`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ cooking_id: cookingId, destination: destination })
        })
        .then(res => res.json())
        .then(response => {
            if (response.success) {
                alert(response.message);
                refreshCookingList(userId);
            } else {
                alert("エラー: " + response.message);
            }
        })
        .catch(error => { alert("差し戻し処理に失敗しました。"); });
    }
 
    // ③ 料理完了
    btnComplete.addEventListener("click", () => {
        if (!confirm("料理を完了し、食材を正式に消費しますか？")) return;
        fetch(`${API_URL}?action=complete`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: userId })
        })
        .then(res => res.json())
        .then(response => {
            if (response.success) {
                alert("美味しい料理ができますように！在庫を更新しました。");
                refreshCookingList(userId);
            } else {
                alert("エラー: " + response.message);
            }
        })
        .catch(error => { alert("完了処理に失敗しました。"); });
    });
 
    // ④ 自動復元
    function autoRestoreExpiredData() {
        fetch(`${API_URL}?action=auto_restore`)
        .then(res => res.json())
        .then(response => {
            if (response.success && response.message !== "復元件数: 0件") {
                console.log("[Auto Restore]: " + response.message);
            }
        })
        .catch(error => console.error("自動復元通信エラー:", error));
    }
});