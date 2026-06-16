// selection_AI_recipe.js
document.addEventListener('DOMContentLoaded', () => {

    // 現在どのページ（ファイル）を開いているかをIDや要素の存在で判定します
    const submitBtn = document.getElementById('submit-to-ai');
    const recipeContainer = document.getElementById('recipe-list-container');

    /* =================================================================================
       A. 【条件選択画面（select_conditions.php）】で動く処理
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
                // ➔ 【処理専用ファイル（suggest_recipe.php）】へお使いを頼む！
                const response = await fetch('../../server/page/AI_recipe_server.php', { method: 'POST', body: formData });
                if (!response.ok) throw new Error('通信に失敗しました。');

                const data = await response.json();

                if (data.success) {
                    // 👑 届いたお土産データをブラウザの一時ポケット（sessionStorage）に格納！
                    sessionStorage.setItem('ai_recipes', JSON.stringify(data.recipes));

                    // 👑 準備ができたら、【結果を表示するファイル（show_result.php）】へジャンプ！
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
       B. 【結果表示画面（show_result.php）】で動く処理
       ================================================================================= */
    if (recipeContainer) {
        // ブラウザの一時ポケットからさっき保存したデータを取り出す
        const savedData = sessionStorage.getItem('ai_recipes');

        if (!savedData) {
            recipeContainer.innerHTML = '<p>レシピデータが見つかりません。条件選択からやり直してください。</p>';
            return;
        }

        const recipes = JSON.parse(savedData);

        // 1個ずつカードにしてHTMLをガシガシ生成し、画面にドッキング！
        recipes.forEach((recipe, index) => {
            const proposalNumber = index + 1;

            let featureBadges = '';
            if (recipe.features && Array.isArray(recipe.features)) {
                featureBadges = recipe.features.map(f => `<span class="feature-badge">[${f}]</span>`).join('');
            }

            const recipeCard = document.createElement('div');
            recipeCard.className = 'recipe-card';

            recipeCard.innerHTML = `
                <div class="recipe-header">
                    <label class="recipe-label" for="recipe-check-${proposalNumber}">⭐ 提案 ${proposalNumber}</label>
                </div>
                <h3 class="recipe-title">料理名：${recipe.recipe_name}</h3>
                <hr class="recipe-divider">
                <p class="recipe-text"><strong>説明：</strong>${recipe.description}</p>
                <div class="badge-container"><strong>特徴：</strong>${featureBadges}</div>
                
                <div style="margin-bottom: 15px;">
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

                // 💡 ユーザーIDと、新設APIの仕様に合わせた食材リストの成形
                const userId = 1; // 実際の環境に合わせてセッション等から取得してください

                if (!chosenRecipe.used_ingredients || !Array.isArray(chosenRecipe.used_ingredients)) {
                    alert("消費する食材データが含まれていません。");
                    btn.disabled = false;
                    btn.innerText = "🍳 この料理にする！（採用）";
                    return;
                }

                const itemsForApi = chosenRecipe.used_ingredients.map(item => {
                    return {
                        id: item.id || item.ingredient_id || null,
                        quantity: parseInt(item.quantity) || 1
                    };
                }).filter(item => item.id !== null);

                if (itemsForApi.length === 0) {
                    alert("消費する食材のIDデータが見つかりません。");
                    btn.disabled = false;
                    btn.innerText = "🍳 この料理にする！（採用）";
                    return;
                }

                // 👑 旧 selection_server.php への通信とアラート、および window.location.href による
                // 別ページへの強制ジャンプを完全に排除し、新設した調理モーダル関数を直接ここで叩く！
                openCookingModal(userId, itemsForApi, btn);
            });
        });
    }
});

/* =================================================================================
   C. 【調理中モーダルダイアログ（画面4）】を生成・制御する関数
   ================================================================================= */
async function openCookingModal(userId, items, originButton) {
    // 24時間放置データの自動復元をバックグラウンドでそっと走らせる
    fetch('../../server/page/cooking_server.php?action=auto_restore').catch(err => console.error(err));

    //すでに古いモーダルが残っていたら、一旦画面から完全に消し去る
    let oldModal = document.getElementById('cooking-now-modal');
    if (oldModal) oldModal.remove();

    // 1. HTML上にモーダル構造がなければ動的に生成して挿入
    let modal = document.getElementById('cooking-now-modal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'cooking-now-modal';
        modal.innerHTML = `
            <div class="cooking-modal-overlay">
                <div class="cooking-modal-content">
                    <button type="button" class="btn-close-modal" id="btn-cooking-close">×</button>

                    <h3 style="margin-top:0;">🍳 料理中...</h3>
                    <p class="cooking-subtitle">使用する食材の一覧です。使わない食材は「冷蔵庫に戻す」を押してください。</p>
                    <hr style="border:0; border-top:1px solid #eee; margin:15px 0;">
                    <div id="cooking-ingredients-list"></div>
                    <hr style="border:0; border-top:1px solid #eee; margin:15px 0;">
                    <button type="button" id="btn-cooking-complete" class="btn-complete">🍳 料理完了！</button>
                </div>
            </div>

            <style>
                /* モーダル全体のコンテナ：画面の左上（0,0）に強制固定 */
                #cooking-now-modal { 
                    position: fixed !important; 
                    top: 0 !important; 
                    left: 0 !important; 
                    width: 100vw !important; 
                    height: 100vh !important; 
                    z-index: 99999 !important; 
                    display: block !important; 
                    margin: 0 !important;
                    padding: 0 !important;
                }

                /* 背景の黒い半透明：ページがどれだけ下に長くても完全に覆い尽くす */
                .cooking-modal-overlay { 
                    position: absolute !important; 
                    top: 0 !important;
                    left: 0 !important;
                    width: 100% !important; 
                    height: 100% !important; 
                    background: rgba(0, 0, 0, 0.6) !important; 
                    z-index: 99998 !important;
                }
                
                /* モーダルの白枠コンテンツ：常に画面のド真ん中に固定 */
                .cooking-modal-content { 
                    position: fixed !important; 
                    top: 50% !important;
                    left: 50% !important;
                    transform: translate(-50%, -50%) !important; 
                    background: #fff !important; 
                    width: 90% !important; 
                    max-width: 440px !important; 
                    max-height: 85vh !important; 
                    overflow-y: auto !important;  
                    padding: 25px !important; 
                    border-radius: 16px !important; 
                    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3) !important; 
                    text-align: left !important; 
                    box-sizing: border-box !important; 
                    z-index: 99999 !important;
                }

                /* ⭕ 「×」ボタン用のデザインCSS */
                .btn-close-modal {
                    position: absolute !important;
                    top: 15px !important;
                    right: 20px !important;
                    background: none !important;
                    border: none !important;
                    font-size: 28px !important;
                    line-height: 1 !important;
                    color: #999 !important;
                    cursor: pointer !important;
                    padding: 5px !important;
                    transition: color 0.2s ease !important;
                    z-index: 100000 !important;
                }
                .btn-close-modal:hover {
                    color: #333 !important;
                }
                
                /* アニメーションも新しい配置方式（translate）に対応 */
                @keyframes modalFadeIn {
                    from { opacity: 0; transform: translate(-50%, -70%); }
                    to { opacity: 1; transform: translate(-50%, -50%); }
                }

                .cooking-subtitle { font-size: 0.85rem; color: #666; margin: 5px 0 15px 0; line-height: 1.4; }
                .ingredient-row { display: flex; justify-content: space-between; align-items: center; padding: 12px 5px; border-bottom: 1px solid #eee; }
                .ingredient-info { font-weight: bold; color: #333; font-size: 0.95rem; }
                .btn-return { background-color: #ff9800; color: white; border: none; padding: 6px 12px; cursor: pointer; border-radius: 6px; font-size: 0.8rem; font-weight: bold; }
                .btn-return:hover { background-color: #e68a00; }
                .btn-complete { display: block; width: 100%; background-color: #4caf50; color: white; border: none; padding: 14px; font-size: 1.1rem; margin-top: 15px; cursor: pointer; border-radius: 8px; font-weight: bold; }
                .btn-complete:hover { background-color: #43a047; }
                .btn-complete:disabled { background-color: #bbb; cursor: not-allowed; }
            </style>
        `;
        document.body.prepend(modal);
    }

    const listContainer = document.getElementById('cooking-ingredients-list');
    const completeBtn = document.getElementById('btn-cooking-complete');
    listContainer.innerHTML = '<p style="text-align:center; color:#999; padding:20px;">⏳ 調理データを同期中...</p>';
    completeBtn.disabled = true;

    try {
        // 2. 【① 調理開始 API】を叩いて、cooking_now テーブルへ移す
        const response = await fetch('../../server/page/cooking_server.php?action=start', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: userId, items: items })
        });

        const result = await response.json();
        if (!result.success) throw new Error(result.message || "調理開始に失敗しました。");

        // 3. バックエンドから返ってきた正確な調理中リスト（result.data）を描画
        renderCookingIngredients(result.data);
        completeBtn.disabled = false;

    } catch (error) {
        alert("エラーが発生しました: " + error.message);
        modal.remove(); // 失敗したらダイアログを片付ける
        if (originButton) {
            originButton.disabled = false;
            originButton.innerText = "🍳 この料理にする！（採用）";
        }
    }

    /**
     * リストを動的に描画するインナー関数
     */
    function renderCookingIngredients(ingredients) {
        listContainer.innerHTML = '';

        if (!ingredients || ingredients.length === 0) {
            listContainer.innerHTML = '<p style="color: #999; text-align: center; padding: 20px;">すべての食材が戻されました。</p>';
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

            // 4. 【② 食材の個別差し戻し API】のバインディング
            row.querySelector('.btn-return').addEventListener('click', async (e) => {
                const cookingNowId = e.target.getAttribute('data-id');
                e.target.disabled = true;
                e.target.innerText = "⏳ 戻し中...";

                try {
                    const returnRes = await fetch('../../server/page/cooking_server.php?action=return_item', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            cooking_id: cookingNowId,
                            destination: 'original'
                        })
                    });

                    const returnResult = await returnRes.json();
                    if (!returnResult.success) throw new Error(returnResult.message);

                    // 成功したらダイアログからシュッと消す
                    document.getElementById(`cooking-item-${cookingNowId}`).remove();

                    if (listContainer.children.length === 0) {
                        listContainer.innerHTML = '<p style="color: #999; text-align: center; padding: 20px;">すべての食材が戻されました。</p>';
                    }

                } catch (err) {
                    alert("差し戻しエラー: " + err.message);
                    e.target.disabled = false;
                    e.target.innerText = "↩️ 冷蔵庫に戻す";
                }
            });
        });
    }

    // 5. 【③ 料理完了 API】一括消費＆画面1への帰還
    completeBtn.onclick = async () => {
        completeBtn.disabled = true;
        completeBtn.innerText = "⏳ 在庫を消費確定中...";

        try {
            const completeRes = await fetch('../../server/page/cooking_server.php?action=complete', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: userId })
            });

            const completeResult = await completeRes.json();
            if (!completeResult.success) throw new Error(completeResult.message);

            alert("美味しく出来上がりました！最初の画面に戻ります。");
            modal.remove();

            // 画面1（一番最初の条件選択画面）へ遷移
            window.location.href = 'AI_recipe.php';

        } catch (err) {
            alert("料理完了エラー: " + err.message);
            completeBtn.disabled = false;
            completeBtn.innerText = "🍳 料理完了！";
        }
    };

    // ⭕ 【6. ×ボタン・背景クリックでモーダルを閉じる処理】
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