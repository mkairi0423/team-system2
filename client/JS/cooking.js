document.addEventListener("DOMContentLoaded", () => {
    const userId = document.getElementById("current-user-id").value;
    const ingredientsListContainer = document.getElementById("ingredients-target-list");
    const btnComplete = document.getElementById("btn-cooking-complete");

    // 画面起動時に24時間放置データの自動復元を走らせる（仕様④）
    autoRestoreExpiredData();

    // テスト用に画面起動時に「調理開始」をエミュレート（実際は画面3の確定ボタンから呼ばれる想定）
    // 本番環境では、確定ボタンのイベント等に置き換えてください
    startCooking(userId);

    // --------------------------------------------------------
    // ① 調理開始（APIを叩いてリストを描画）
    // --------------------------------------------------------
    function startCooking(uid) {
        // 本来は画面3の選択データが入る
        const mockItems = [
            { id: 1, quantity: 2 }, 
            { id: 2, quantity: 1 }
        ];

        fetch('../page/cooking_server.php?action=start', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: uid, items: mockItems })
        })
        .then(res => res.json())
        .then(response => {
            if (response.success) {
                renderIngredients(response.data);
            } else {
                alert("エラー: " + response.message);
            }
        });
    }

    // --------------------------------------------------------
    // リストの描画処理（HTMLを動的に生成）
    // --------------------------------------------------------
    function renderIngredients(items) {
        ingredientsListContainer.innerHTML = "";

        if (items.length === 0) {
            ingredientsListContainer.innerHTML = "<p>調理中の食材はありません。</p>";
            return;
        }

        items.forEach(item => {
            const div = document.createElement("div");
            div.className = "ingredient-item";
            div.innerHTML = `
                <span class="item-info"><strong>${item.food}</strong> (${item.quantity})</span>
                <div class="item-controls">
                    <select class="select-destination" id="dest-${item.id}">
                        <option value="original">元の場所</option>
                        <option value="fridge">冷蔵庫</option>
                        <option value="freezer">冷凍庫</option>
                    </select>
                    <button type="button" class="btn-return" data-id="${item.id}">戻す</button>
                </div>
            `;
            ingredientsListContainer.appendChild(div);
        });

        // 「戻す」ボタンのイベントリスナー設定（仕様②）
        document.querySelectorAll(".btn-return").forEach(button => {
            button.addEventListener("click", (e) => {
                const cookingId = e.target.getAttribute("data-id");
                const destination = document.getElementById(`dest-${cookingId}`).value;
                returnItem(cookingId, destination);
            });
        });
    }

    // --------------------------------------------------------
    // ② 食材の個別差し戻し
    // --------------------------------------------------------
    function returnItem(cookingId, destination) {
        fetch('../page/cooking_server.php?action=return_item', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ cooking_id: cookingId, destination: destination })
        })
        .then(res => res.json())
        .then(response => {
            if (response.success) {
                alert(response.message);
                // 差し戻し成功後、リストを再取得（または画面上の要素を消去）
                startCooking(userId); 
            } else {
                alert("エラー: " + response.message);
            }
        });
    }

    // --------------------------------------------------------
    // ③ 料理完了
    // --------------------------------------------------------
    btnComplete.addEventListener("click", () => {
        if (!confirm("料理を完了し、食材を消費しますか？")) return;

        fetch('../page/cooking_server.php?action=complete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: userId })
        })
        .then(res => res.json())
        .then(response => {
            if (response.success) {
                alert("美味しい料理ができますように！在庫を更新しました。");
                // ここでトップ画面や冷蔵庫一覧画面へ遷移させる
                // window.location.href = 'fridge_list.php';
            } else {
                alert("エラー: " + response.message);
            }
        });
    });

    // --------------------------------------------------------
    // ④ 24時間放置データの自動復元
    // --------------------------------------------------------
    function autoRestoreExpiredData() {
        fetch('../page/cooking_server.php?action=auto_restore')
        .then(res => res.json())
        .then(response => {
            if (response.success && response.message.includes("処理件数: 0件") === false) {
                console.log(response.message); // ログにだけ残す
            }
        });
    }
});