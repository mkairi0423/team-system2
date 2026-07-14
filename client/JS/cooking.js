const API_URL = '../../server/page/cooking_server.php';

document.addEventListener("DOMContentLoaded", () => {
    const userIdEl = document.getElementById("current-user-id");
    const userId = userIdEl ? userIdEl.value : 1;

    const ingredientsListContainer = document.getElementById("ingredients-target-list");
    const btnComplete = document.getElementById("btn-cooking-complete");
    const confirmBtn = document.getElementById("btn-recipe-confirm");
    const newFoodSelect = document.getElementById("new-food-select");
    const newFoodQty = document.getElementById("new-food-qty");
    const newFoodUnitDisplay = document.getElementById("new-food-unit-display");
    const btnAddIngredient = document.getElementById("btn-add-ingredient");

    // アコーディオン・数量ボタン系（ここで定義！）
    const accordionSection = document.querySelector(".add-extra-section");
    const accordionToggle = document.getElementById("accordion-toggle");
    const btnQtyMinus = document.getElementById("btn-qty-minus");
    const btnQtyPlus = document.getElementById("btn-qty-plus");

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

    // 調理開始処理
    async function startCooking(items = []) {
        const result = await apiRequest('start', { items: items });
        if (result.success) renderIngredients(result.data);
        else alert("開始エラー: " + result.message);
    }

    // リストの描画（item.unitを正しく使う）
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
            // ★サーバーから送られた unit をそのまま表示
            div.innerHTML = `
                <div class="ingredient-info-wrapper">
                    <span class="ingredient-name">${item.food}</span>
                    <span class="ingredient-qty">(${Number(item.quantity)}${item.unit})</span>
                </div>
                <button class="btn-return-direct" data-id="${item.id}">↩️ 戻す</button>
            `;
            ingredientsListContainer.appendChild(div);
        });
    }

    // 料理完了ボタン（遷移先パスを適宜修正してください）
    if (btnComplete) {
        btnComplete.addEventListener("click", async () => {
            if (!confirm("料理を完了して履歴に保存しますか？")) return;
            const recipeNameEl = document.getElementById("cooking-recipe-name");
            const dishName = recipeNameEl ? recipeNameEl.innerText.replace("🍳 ", "").trim() : "不明な料理";

            try {
                btnComplete.disabled = true;
                const result = await apiRequest('complete', { dish_name: dishName });
                if (result.success) {
                    window.location.href = '../../index.php'; // ★ここを正しい遷移先に
                } else {
                    throw new Error(result.message);
                }
            } catch (error) {
                alert("保存エラー: " + error.message);
                btnComplete.disabled = false;
            }
        });
    }

    // リスト追加処理（単位の取得を修正）
    if (btnAddIngredient) {
        btnAddIngredient.addEventListener("click", async () => {
            const selectedOption = newFoodSelect.options[newFoodSelect.selectedIndex];
            const foodName = newFoodSelect.value;
            const qty = parseFloat(newFoodQty.value);
            const unit = selectedOption ? (selectedOption.dataset.unit || '個') : '個';

            if (!foodName || isNaN(qty)) return alert("食材と数量を入力してください");

            const addItem = [{
                food: foodName,
                use_quantity: qty,
                unit: unit // ★ここで選択された単位を渡す
            }];

            await startCooking(addItem);
            await refreshList();
        });
    }

    async function refreshList() {
        if (!ingredientsListContainer) return;
        const result = await apiRequest('get_list');
        if (result.success) renderIngredients(result.data);
    }

    // ==========================================
    // 2. 料理完了ボタンの処理
    // ==========================================
    if (btnComplete) {
        btnComplete.addEventListener("click", async () => {
            if (!confirm("料理を完了して履歴に保存しますか？")) return;

            // 画面上の料理名を取得（HTMLのh2などから）
            const recipeNameEl = document.getElementById("cooking-recipe-name");
            const dishName = recipeNameEl ? recipeNameEl.innerText.replace("🍳 ", "").trim() : "不明な料理";

            try {
                btnComplete.disabled = true;
                btnComplete.innerText = "⏳ 保存中...";

                // アクション名を 'complete' にし、dish_name を payload に含める
                const result = await apiRequest('complete', { dish_name: dishName });

                if (result.success) {
                    window.location.href = 'home.php';
                } else {
                    throw new Error(result.message);
                }
            } catch (error) {
                alert("保存エラー: " + error.message);
                btnComplete.disabled = false;
                btnComplete.innerText = "🍳 料理完了！履歴に保存";
            }
        });
    }

    // ==========================================
    // 3. その他機能（描画・追加・アコーディオン）
    // ==========================================
    async function startCooking(items = []) {
        const result = await apiRequest('start', { items: items });
        if (result.success) renderIngredients(result.data);
        else alert("開始エラー: " + result.message);
    }

    async function refreshList() {
        if (!ingredientsListContainer) return;
        const result = await apiRequest('get_list');
        if (result.success) renderIngredients(result.data);
    }

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
            
            div.innerHTML = `
            <div class="ingredient-info-wrapper">
                <span class="ingredient-name">${item.food}</span>
                <span class="ingredient-qty">(${Number(item.quantity)}${item.unit})</span>
            </div>
            <button class="btn-return-direct" data-id="${item.id}">↩️ 戻す</button>
        `;
            ingredientsListContainer.appendChild(div);
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
    } else {
        // コンソールに警告だけ出して、処理を中断しないようにします
        console.warn("アコーディオン用の要素が見つかりませんでした。HTMLを確認してください。");
    }

    // ② 数量の「＋/－」ステップ計算制御
    // 例：数量ボタン処理
    if (btnQtyMinus && btnQtyPlus && newFoodQty) {
        btnQtyMinus.addEventListener("click", () => {
            let currentVal = parseFloat(newFoodQty.value) || 0;
            if (currentVal > 0.1) {
                newFoodQty.value = (currentVal - 1 < 0.1) ? 0.5 : (currentVal - 1);
            }
        });

        btnQtyPlus.addEventListener("click", () => {
            let currentVal = parseFloat(newFoodQty.value) || 0;
            newFoodQty.value = (currentVal === 0.5) ? 1 : (currentVal + 1);
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
    if (confirmBtn) {
        confirmBtn.addEventListener("click", async (e) => {
            // 💡 取得できているか確認（コンソールで見てください）
            const dish = e.currentTarget.dataset.dish;
            console.log("取得した料理名:", dish);

            if (!dish) {
                alert("エラー: 料理名が取得できませんでした。");
                return;
            }

            if (!confirm(`「${dish}」の調理を開始しますか？`)) return;

            try {
                confirmBtn.disabled = true;
                confirmBtn.innerText = "⏳ 食材リストを取得中...";

                const result = await apiRequest('get_needed_ingredients', { keyword: dish });

                if (!result.success) {
                    throw new Error(result.message || '食材の抽出に失敗しました。');
                }

                // 💡 HTMLを強制的に書き換える
                const recipeNameEl = document.getElementById("cooking-recipe-name");
                if (recipeNameEl) {
                    recipeNameEl.innerText = "🍳 " + dish;
                    console.log("画面を書き換えました:", recipeNameEl.innerText);
                }

                const cookingItems = result.data.map(foodName => {
                    return { id: null, food: foodName, use_quantity: null };
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

    // ページ離脱時のキャンセル処理
    window.addEventListener("pagehide", () => {
        if (btnComplete && !btnComplete.disabled) {
            const url = `${API_URL}?action=cancel`;
            const data = JSON.stringify({ user_id: userId });
            if (navigator.sendBeacon) {
                navigator.sendBeacon(url, new Blob([data], { type: 'application/json' }));
            }
        }
    });

    refreshList();
    loadUserIngredients();
});

// HTML側の onclick="toggleMenu()" 対策（メニュー用関数を定義）
window.toggleMenu = function () {
    const sidebar = document.querySelector('.sidebar');
    if (sidebar) sidebar.classList.toggle('active');
};