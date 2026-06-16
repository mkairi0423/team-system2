document.addEventListener("DOMContentLoaded", () => {
    // HTMLから要素を取得
    const userId = document.getElementById("current-user-id").value;
    const ingredientsListContainer = document.getElementById("ingredients-target-list");
    const btnComplete = document.getElementById("btn-cooking-complete");

    // バックエンド（API）のパス
    const API_URL = '../../server/page/cooking_server.php';

    // 1. 画面起動時に24時間放置データの自動復元を走らせる（仕様④）
    autoRestoreExpiredData();

    // 2. 画面起動時の処理
    // ※テスト用にモックデータで調理を開始します。
    // ※本番環境（前画面からキープ済みの状態で遷移してくる場合）は、
    //   ここを「refreshCookingList(userId);」だけに差し替えてください。
    startCooking(userId);


    // --------------------------------------------------------
    // ① 調理開始（APIを叩いて新規キープし、リストを描画）
    // --------------------------------------------------------
    function startCooking(uid) {
        // テスト用のモックデータ（本来は前の画面で選ばれた食材が入る）
        const mockItems = [
            { id: 1, quantity: 2 },
            { id: 2, quantity: 1 }
        ];

        fetch(`${API_URL}?action=start`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: uid, items: mockItems })
        })
            .then(res => res.json())
            .then(response => {
                if (response.success) {
                    renderIngredients(response.data);
                } else {
                    alert("調理開始エラー: " + response.message);
                }
            })
            .catch(error => {
                console.error("通信エラー:", error);
                alert("サーバーとの通信に失敗しました。");
            });
    }

    // --------------------------------------------------------
    // 【重要】調理中リストの最新状態を「ただ取得するだけ」の処理
    // --------------------------------------------------------
    function refreshCookingList(uid) {
        // itemsを空配列 [] で送ることで、API側の新規削除・挿入をスキップさせ、
        // 現在の cooking_now の中身だけを安全に取得します。
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
    // リストの描画処理（HTMLを動的に生成）
    // --------------------------------------------------------
    function renderIngredients(items) {
        ingredientsListContainer.innerHTML = "";

        if (!items || items.length === 0) {
            ingredientsListContainer.innerHTML = "<p>調理中の食材はありません。</p>";
            btnComplete.disabled = true; // 食材がない時は完了ボタンを押せなくする
            return;
        }

        btnComplete.disabled = false;

        items.forEach(item => {
            const div = document.createElement("div");
            div.className = "ingredient-item";

            // スタイルはCSS（cooking.css）で調整するのが理想ですが、
            // レイアウトが崩れないよう最低限の調整をJS側でも付与しています
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

        // 「戻す」ボタンのイベントリスナー設定（仕様②）
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

    // --------------------------------------------------------
    // ② 食材の個別差し戻し
    // --------------------------------------------------------
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
                    // 🟢 startCooking ではなく、安全な再読み込み関数を呼ぶ
                    refreshCookingList(userId);
                } else {
                    alert("エラー: " + response.message);
                }
            })
            .catch(error => {
                console.error("通信エラー:", error);
                alert("差し戻し処理に失敗しました。");
            });
    }

    // --------------------------------------------------------
    // ③ 料理完了
    // --------------------------------------------------------
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

                    // リストをクリア
                    refreshCookingList(userId);

                    // 💡 本番用：ここでトップ画面や冷蔵庫一覧画面へ遷移させる場合
                    // window.location.href = 'fridge_list.php';
                } else {
                    alert("エラー: " + response.message);
                }
            })
            .catch(error => {
                console.error("通信エラー:", error);
                alert("完了処理に失敗しました。");
            });
    });

    // --------------------------------------------------------
    // ④ 24時間放置データの自動復元
    // --------------------------------------------------------
    function autoRestoreExpiredData() {
        fetch(`${API_URL}?action=auto_restore`)
            .then(res => res.json())
            .then(response => {
                // ログのノイズを減らすため、実際に復元されたデータがある場合のみコンソールに出力
                if (response.success && !response.message.includes("処理件数: 0件")) {
                    console.log("[Auto Restore]: " + response.message);
                }
            })
            .catch(error => console.error("自動復元通信エラー:", error));
    }
});