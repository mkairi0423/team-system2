// selection_AI_recipe.js
document.addEventListener('DOMContentLoaded', () => {

    // 現在どのページ（ファイル）を開いているかをIDや要素の存在で判定します
    const submitBtn = document.getElementById('submit-to-ai');
    const recipeContainer = document.getElementById('recipe-list-container');

    /* =================================================================================
       A. 【条件選択画面】で動く処理
       ================================================================================= */
    if (submitBtn) {
        const optionGroups = document.querySelectorAll('.grid-options');

        // ① ボタンのカチカチ切り替え処理
        optionGroups.forEach(group => {
            const buttons = group.querySelectorAll('.option-btn');
            buttons.forEach(button => {
                button.addEventListener('click', () => {
                    const currentSelected = group.querySelector('.option-btn.selected');
                    if (currentSelected) currentSelected.classList.remove('selected');
                    button.classList.add('selected');
                });
            });
        });

        // ② 「レシピを聞く」ボタンを押したときの裏通信（Fetch）処理
        submitBtn.addEventListener('click', async () => {
            submitBtn.disabled = true;
            submitBtn.innerText = '⏳ AIシェフがレシピを考案中...';

            const formData = new FormData();
            optionGroups.forEach(group => {
                const groupName = group.getAttribute('data-group');
                const selectedBtn = group.querySelector('.option-btn.selected');
                if (selectedBtn) {
                    formData.append(groupName, selectedBtn.getAttribute('data-value'));
                }
            });

            try {
                const response = await fetch('../../server/page/AI_recipe_server.php', { method: 'POST', body: formData });
                if (!response.ok) throw new Error('通信に失敗しました。');

                const data = await response.json();

                if (data.success) {
                    // 👑 届いたデータをブラウザの一時ポケット（sessionStorage）に格納！
                    // オブジェクトの階層ごとまるごと保存してもバグらないようにJS側で受け口を強化しました
                    sessionStorage.setItem('ai_recipes', JSON.stringify(data.recipes));

                    // 👑 準備ができたら、【結果を表示するファイル（selection.php）】へジャンプ！
                    window.location.href = 'selection.php';
                } else {
                    alert('エラー: ' + data.error);
                }
            } catch (error) {
                alert('通信失敗: ' + error.message);
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerText = '🚀 この条件でAIシェフにレシピを聞く！';
            }
        });
    }

    /* =================================================================================
       B. 【結果表示画面（selection.php）】で動く処理
       ================================================================================= */
    if (recipeContainer) {
        // ブラウザの一時ポケットからさっき保存したデータを取り出す
        const savedData = sessionStorage.getItem('ai_recipes');

        if (!savedData) {
            recipeContainer.innerHTML = '<p>レシピデータが見つかりません。条件選択からやり直してください。</p>';
            return;
        }

        const responseData = JSON.parse(savedData);

        // 💡 データのルートが { success: true, recipes: [...] } の形でも、直接配列が入っていても安全に抽出
        const recipes = Array.isArray(responseData) ? responseData : (responseData.recipes || []);

        if (recipes.length === 0) {
            recipeContainer.innerHTML = '<p>提案できるレシピがありませんでした。</p>';
            return;
        }

        // 1個ずつカードにしてHTMLを生成し、画面にドッキング！
        recipes.forEach((recipe, index) => {
            const proposalNumber = index + 1;

            // 特徴バッジの生成
            let featureBadges = '';
            if (recipe.features && Array.isArray(recipe.features)) {
                featureBadges = recipe.features.map(f => `<span class="feature-badge">[${f}]</span>`).join('');
            }

            // 消費する食材リストのHTMLを生成する処理
            let ingredientsHtml = '<ul>';
            if (recipe.used_ingredients && Array.isArray(recipe.used_ingredients)) {
                recipe.used_ingredients.forEach(ing => {
                    const ingName = ing.name || ing.food || "不明な食材";
                    const ingQty = ing.quantity || ing.amount_used || "適量";
                    ingredientsHtml += `<li>${ingName} : ${ingQty}</li>`;
                });
            } else {
                ingredientsHtml += '<li>使用する食材データがありません</li>';
            }
            ingredientsHtml += '</ul>';

            const recipeCard = document.createElement('div');
            recipeCard.className = 'recipe-card';

            recipeCard.innerHTML = `
                <div class="recipe-header">
                    <label class="recipe-label" for="recipe-check-${proposalNumber}">⭐ 提案 ${proposalNumber}</label>
                </div>
                <h3 class="recipe-title">料理名：${recipe.recipe_name}</h3>
                <hr class="recipe-divider">
                <p class="recipe-text"><strong>説明：</strong>${recipe.description}</p>
                
                <div class="recipe-ingredients">
                    <strong>使う食材：</strong>
                    ${ingredientsHtml}
                </div>

                <div class="badge-container"><strong>特徴：</strong>${featureBadges}</div>
                
                <div>
                    <a href="${recipe.google_search_url}" class="search-link" target="_blank">🔍 [ネットで料理の見た目や作り方を調べる (Googleリンク)]</a>
                </div>
                
                <div class="adopt-container">
                    <button type="button" class="btn-adopt" data-index="${index}">🍳 この料理にする！（採用）</button>
                </div>
            `;

            recipeContainer.appendChild(recipeCard);
        });

        // 「この料理にする！」ボタンのイベント処理
        const adoptButtons = recipeContainer.querySelectorAll('.btn-adopt');

        adoptButtons.forEach(btn => {
            btn.addEventListener('click', async () => {

                const idx = btn.getAttribute('data-index');
                const chosenRecipe = recipes[idx];

                console.log("中身", chosenRecipe.used_ingredients);

                // ✅ 連打防止
                btn.disabled = true;
                btn.innerText = "⏳ 調理準備中...";

                // 💡 一時保存ポケットにレシピを記憶
                sessionStorage.setItem('selected_recipe', JSON.stringify(chosenRecipe));

                const userId = 1; // 実際の環境に合わせてセッション等から取得してください

                if (!chosenRecipe.used_ingredients || !Array.isArray(chosenRecipe.used_ingredients)) {
                    alert("消費する食材データが含まれていません。");
                    btn.disabled = false;
                    btn.innerText = "🍳 この料理にする！（採用）";
                    return;
                }

                // プロンプト側のキー名（name や quantity）を、新設APIが期待する形に変換
                const itemsForApi = chosenRecipe.used_ingredients.map(item => {
                    return {
                        id: item.id || item.ingredient_id || null,
                        quantity: parseInt(item.quantity) || parseFloat(item.amount_used) || 1
                    };
                }).filter(item => item.id !== null);

                if (itemsForApi.length === 0) {
                    alert("消費する食材のIDデータが見つかりません。");
                    btn.disabled = false;
                    btn.innerText = "🍳 この料理にする！（採用）";
                    return;
                }

                // 調理モーダル関数を直接ここで叩く！
                openCookingModal(userId, itemsForApi, btn);
            });
        });
    }
});

/* =================================================================================
   C. 【調理中モーダルダイアログ】を生成・制御する関数
   ================================================================================= */
async function openCookingModal(userId, items, originButton) {
    fetch('../../server/page/cooking_server.php?action=auto_restore').catch(err => console.error(err));

    let oldModal = document.getElementById('cooking-now-modal');
    if (oldModal) oldModal.remove();

    let modal = document.getElementById('cooking-now-modal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'cooking-now-modal';
        modal.innerHTML = `
            <div class="cooking-modal-overlay">
                <div class="cooking-modal-content">
                    <button type="button" class="btn-close-modal" id="btn-cooking-close">×</button>

                    <h3>🍳 料理中...</h3>
                    <p class="cooking-subtitle">使用する食材の一覧です。使わない食材は「冷蔵庫に戻す」を押してください。</p>
                    <hr>
                    <div id="cooking-ingredients-list"></div>
                    <hr>
                    <button type="button" id="btn-cooking-complete" class="btn-complete">🍳 料理完了！</button>
                </div>
            </div>
        `;
        document.body.prepend(modal);
    }

    const listContainer = document.getElementById('cooking-ingredients-list');
    const completeBtn = document.getElementById('btn-cooking-complete');
    listContainer.innerHTML = '<p>⏳ 調理データを同期中...</p>';
    completeBtn.disabled = true;

    try {
        const response = await fetch('../../server/page/cooking_server.php?action=start', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: userId, items: items })
        });

        const result = await response.json();
        if (!result.success) throw new Error(result.message || "調理開始に失敗しました。");

        renderCookingIngredients(result.data);
        completeBtn.disabled = false;

    } catch (error) {
        alert("エラーが発生しました: " + error.message);
        modal.remove();
        if (originButton) {
            originButton.disabled = false;
            originButton.innerText = "🍳 この料理にする！（採用）";
        }
    }

    function renderCookingIngredients(ingredients) {
        listContainer.innerHTML = '';

        if (!ingredients || ingredients.length === 0) {
            listContainer.innerHTML = '<p>すべての食材が戻されました。</p>';
            return;
        }

        ingredients.forEach(item => {
            const row = document.createElement('div');
            row.className = 'ingredient-row';
            row.id = `cooking-item-${item.id}`;
            row.innerHTML = `
                <span class="ingredient-info">🔸 ${item.food}（${item.quantity}${item.unit}）</span>
                <button type="button" class="btn-return" data-id="${item.id}">↩️ 冷蔵庫に戻す</button>
            `;
            listContainer.appendChild(row);

            row.querySelector('.btn-return').addEventListener('click', async (e) => {
                const cookingNowId = e.target.getAttribute('data-id');
                e.target.disabled = true;
                e.target.innerText = "⏳ 戻し中...";

                try {
                    const returnRes = await fetch('../../server/page/cooking_server.php?action=return_item', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ cooking_id: cookingNowId, destination: 'original' })
                    });

                    const returnResult = await returnRes.json();
                    if (!returnResult.success) throw new Error(returnResult.message);

                    document.getElementById(`cooking-item-${cookingNowId}`).remove();

                    if (listContainer.children.length === 0) {
                        listContainer.innerHTML = '<p>すべての食材が戻されました。</p>';
                    }

                } catch (err) {
                    alert("差し戻しエラー: " + err.message);
                    e.target.disabled = false;
                    e.target.innerText = "↩️ 冷蔵庫に戻す";
                }
            });
        });
    }

    completeBtn.onclick = async () => {
        completeBtn.disabled = true;
        completeBtn.innerText = "⏳ 在庫を消費確定中...";

        let dishName = "AI考案料理";
        try {
            const savedRecipe = JSON.parse(sessionStorage.getItem('selected_recipe') || '{}');
            if (savedRecipe && savedRecipe.recipe_name) {
                dishName = savedRecipe.recipe_name;
            }
        } catch (e) { console.error(e); }

        try {
            const completeRes = await fetch('../../server/page/cooking_server.php?action=complete', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: userId, dish_name: dishName })
            });

            const completeResult = await completeRes.json();
            if (!completeResult.success) throw new Error(completeResult.message);

            alert("美味しく出来上がりました！料理履歴に保存しました");
            modal.remove();
            window.location.href = 'AI_recipe.php';

        } catch (err) {
            alert("料理完了エラー: " + err.message);
            completeBtn.disabled = false;
            completeBtn.innerText = "🍳 料理完了！";
        }
    };

    const closeBtn = document.getElementById('btn-cooking-close');
    const overlay = modal.querySelector('.cooking-modal-overlay');

    const closeModalClosure = () => {
        modal.remove();
        if (originButton) {
            originButton.disabled = false;
            originButton.innerText = "🍳 この料理にする！（採用）";
        }
    };

    if (closeBtn) closeBtn.addEventListener('click', closeModalClosure);
    if (overlay) overlay.addEventListener('click', closeModalClosure);
}