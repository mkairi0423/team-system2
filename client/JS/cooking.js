/**
 * cooking.js - 調理中食材管理・一覧取得スクリプト (開閉バグ修正版)
 */
document.addEventListener("DOMContentLoaded", () => {
    const userIdEl = document.getElementById("current-user-id");
    const userId = userIdEl ? userIdEl.value : 1;
    const ingredientsListContainer = document.getElementById("ingredients-target-list");
    const btnComplete = document.getElementById("btn-cooking-complete");
    const confirmBtn = document.getElementById("btn-recipe-confirm");

    // 追加エリアの各種要素
    const newFoodSelect = document.getElementById("new-food-select");
    const newFoodQty = document.getElementById("new-food-qty");
    const newFoodUnitDisplay = document.getElementById("new-food-unit-display");
    const btnAddIngredient = document.getElementById("btn-add-ingredient");

    // UIパーツ
    const accordionSection = document.querySelector(".add-extra-section");
    const accordionToggle = document.getElementById("accordion-toggle");
    const btnQtyMinus = document.getElementById("btn-qty-minus");
    const btnQtyPlus = document.getElementById("btn-qty-plus");

    // APIの中継先URL
    const API_URL = '../../server/page/cooking_server.php';

    /**
     * サーバーとJSON形式で非同期通信を行う共通関数
     */
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

    // ==========================================
    // 1. 提案確定時の処理（AI食材抽出 ➔ 調理開始）
    // ==========================================
    if (confirmBtn) {
        confirmBtn.addEventListener("click", async (e) => {
            const dish = e.currentTarget.dataset.dish;
            if (!confirm(`「${dish}」の調理を開始しますか？\n必要な食材を一覧に取得します。`)) return;

            try {
                confirmBtn.disabled = true;
                confirmBtn.innerText = "⏳ 食材リストを取得中...";

                const result = await apiRequest('get_needed_ingredients', { keyword: dish });

                if (!result.success) {
                    throw new Error(result.message || '食材の抽出に失敗しました。');
                }

                const cookingItems = result.data.map(foodName => {
                    return {
                        id: null,
                        food: foodName,
                        use_quantity: null
                    };
                });

                await startCooking(cookingItems);

            } catch (error) {
                alert("確定処理でエラーが発生しました: " + error.message);
            } finally {
                confirmBtn.disabled = false;
                confirmBtn.innerText = `🍳 提案された「${dish}」で作る！`;
            }
        });
    }

    // ==========================================
    // 2. 調理中画面の基本機能ロジック
    // ==========================================

    // 調理の開始処理
    async function startCooking(items = []) {
        const result = await apiRequest('start', { items: items });
        if (result.success) {
            renderIngredients(result.data);
        } else {
            alert("調理開始エラー: " + result.message);
        }
    }

    // 調理中リストの最新化
    async function refreshList() {
        if (!ingredientsListContainer) return;
        const result = await apiRequest('get_list');
        if (result.success) renderIngredients(result.data);
    }

    // 食材を在庫に戻す処理
    async function returnItem(cookingId, destination) {
        const result = await apiRequest('return_item', {
            cooking_id: cookingId,
            destination: destination
        });
        if (result.success) {
            alert("在庫に戻しました");
            refreshList();
        } else {
            alert("エラー: " + result.message);
        }
    }

    // 食材リストのHTML描画（使う材料一覧の反映：ボタン一発化版）
    function renderIngredients(items) {
        if (!ingredientsListContainer) return;
        ingredientsListContainer.innerHTML = "";

        if (!items || items.length === 0) {
            ingredientsListContainer.innerHTML = "<p style='color:#999;'>調理中の食材はありません。</p>";
            if (btnComplete) btnComplete.disabled = true;
            return;
        }

        if (btnComplete) btnComplete.disabled = false;

        items.forEach(item => {
            const div = document.createElement("div");
            div.className = "ingredient-item";

            const displayQuantity = Number(item.quantity);

            const isMissing = item.original_storage_location_id === null;
            const badge = isMissing ? "<span style='color:red; font-size:0.8rem; margin-left:5px;'>⚠️要買い足し</span>" : "";

            // 💡 セレクトボックスを削除し、ボタンを押しやすい位置に配置
            div.innerHTML = `
                <div class="ingredient-info">
                    <strong>${item.food}</strong> (${displayQuantity}${item.unit})${badge}
                </div>
                <div class="item-controls-simple">
                    <button class="btn-return-direct" data-id="${item.id}">↩️ 在庫に戻す</button>
                </div>
            `;
            ingredientsListContainer.appendChild(div);
        }); items.forEach(item => {
            const div = document.createElement("div");
            div.className = "ingredient-item";

            const displayQuantity = Number(item.quantity);
            const isMissing = item.original_storage_location_id === null;
            const badge = isMissing ? "<span class='missing-badge'>⚠️ 不足 </span>" : "";

            // 💡 左右にきれいに配置するために構造を整理
            div.innerHTML = `
                <div class="ingredient-info-wrapper">
                    <span class="ingredient-name">${item.food}</span>
                    <span class="ingredient-qty">(${displayQuantity}${item.unit})</span>
                    ${badge}
                </div>
                <div class="item-controls-simple">
                    <button class="btn-return-direct" data-id="${item.id}">↩️ 戻す</button>
                </div>
            `;
            ingredientsListContainer.appendChild(div);
        });

        // 💡 1タップで「original（元の場所）」へ戻すイベントを設定
        const returnButtons = ingredientsListContainer.querySelectorAll(".btn-return-direct");
        returnButtons.forEach(btn => {
            btn.addEventListener("click", (e) => {
                const cookingId = e.currentTarget.dataset.id;
                // 💡 第2引数を 'original' 固定にして一発で戻す
                returnItem(cookingId, 'original');
            });
        });
    }

    // ==========================================
    // 3. 在庫食材の追加プルダウン ＆ アコーディオン制御
    // ==========================================

    // ① アコーディオンの開閉制御
    if (accordionToggle && accordionSection) {
        accordionToggle.addEventListener("click", () => {
            accordionSection.classList.toggle("is-open");
        });
    }

    // ② 数量の「＋/－」ステップ計算制御
    if (btnQtyMinus && btnQtyPlus && newFoodQty) {
        btnQtyMinus.addEventListener("click", () => {
            let currentVal = parseFloat(newFoodQty.value) || 0;
            if (currentVal > 0.1) {
                newFoodQty.value = (currentVal - 1 < 0.1) ? 0.5 : (currentVal - 1);
            }
        });

        btnQtyPlus.addEventListener("click", () => {
            let currentVal = parseFloat(newFoodQty.value) || 0;
            if (currentVal === 0.5) {
                newFoodQty.value = 1;
            } else {
                newFoodQty.value = currentVal + 1;
            }
        });
    }

    // 在庫マスターデータの読み込み関数
    async function loadUserIngredients() {
        if (!newFoodSelect) return;
        try {
            const result = await apiRequest('get_user_ingredients');
            if (result.success && result.data.length > 0) {
                newFoodSelect.innerHTML = '<option value="">-- 追加する食材を選択 --</option>';

                result.data.forEach(item => {
                    const option = document.createElement("option");
                    option.value = item.food_name;
                    option.dataset.unit = item.unit;
                    option.textContent = `${item.food_name} (${item.unit})`;
                    newFoodSelect.appendChild(option);
                });
            } else {
                newFoodSelect.innerHTML = '<option value="">在庫に食材がありません</option>';
            }
        } catch (e) {
            newFoodSelect.innerHTML = '<option value="">読み込み失敗</option>';
        }
    }

    // 在庫プルダウン変更時の単位連動
    if (newFoodSelect) {
        newFoodSelect.addEventListener("change", () => {
            const selectedOption = newFoodSelect.options[newFoodSelect.selectedIndex];
            const unit = selectedOption ? (selectedOption.dataset.unit || '個') : '個';
            if (newFoodUnitDisplay) {
                newFoodUnitDisplay.textContent = unit;
            }
        });
    }

    // ③ 「リストに追加」ボタンの実行処理
    if (btnAddIngredient) {
        btnAddIngredient.disabled = false; // 強制有効化

        btnAddIngredient.addEventListener("click", async () => {
            const foodName = newFoodSelect.value;
            const qty = parseFloat(newFoodQty.value);

            if (!foodName) {
                alert("追加する食材を選択してください。");
                return;
            }
            if (isNaN(qty) || qty <= 0) {
                alert("正しい数量を入力してください。");
                return;
            }

            const addItem = [{
                id: null,
                food: foodName,
                use_quantity: qty
            }];

            try {
                btnAddIngredient.disabled = true;
                btnAddIngredient.innerText = "⏳ 追加中...";

                await startCooking(addItem);

                // 入力状態のリセット
                newFoodSelect.value = "";
                newFoodQty.value = "1";
                if (newFoodUnitDisplay) newFoodUnitDisplay.textContent = "個";

                await refreshList();

                // 追加後はアコーディオンを自動で閉じる
                if (accordionSection) {
                    accordionSection.classList.remove("is-open");
                }

                // alert(`「${foodName}」を調理リストに追加しました。`);

            } catch (error) {
                alert("追加エラー: " + error.message);
            } finally {
                btnAddIngredient.disabled = false;
                btnAddIngredient.innerText = "リストに追加";
            }
        });
    }

    // 料理完了処理
    if (btnComplete) {
        btnComplete.addEventListener("click", async () => {
            if (!confirm("料理を完了しますか？")) return;
            const result = await apiRequest('complete');
            if (result.success) {
                alert("在庫を更新しました！料理履歴に保存されました。");
                refreshList();
            } else {
                alert("完了エラー: " + result.message);
            }
        });
    }

    // ==========================================
    // 4. 初期起動処理
    // ==========================================
    refreshList();
    loadUserIngredients();

    // 戻るボタン対策
    window.addEventListener('pageshow', (event) => {
        if (event.persisted) window.location.reload();
    });
});

// ==========================================
// 6. 【新規】途中で戻ったときの自動クリーンアップ（キャンセル処理）
// ==========================================

// 💡 画面から離脱するときに調理中データを削除する関数
function cancelCooking() {
    // 通常の fetch だと画面が閉じる際に通信が途切れる可能性があるため、
    // 確実にバックグラウンドで処理を送れる「navigator.sendBeacon」または同期的な fetch を使います
    const url = `${API_URL}?action=cancel`;
    const data = JSON.stringify({ user_id: userId });

    if (navigator.sendBeacon) {
        navigator.sendBeacon(url, new Blob([data], { type: 'application/json' }));
    } else {
        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: data,
            keepalive: true // 画面が閉じられても通信を維持する
        });
    }
}

// 📱 スマホのブラウザバックやタブ閉じ、ホームへ戻る動きを検知
window.addEventListener("pagehide", (event) => {
    // 💡 「料理完了」ボタンを押して正常終了した時以外は、すべてキャンセルとみなす
    if (btnComplete && !btnComplete.disabled) {
        // まだ完了ボタンが押せる＝完了せずに画面を離れようとしている
        cancelCooking();
    }
});