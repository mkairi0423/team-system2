//selection_AI_recipe.js
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
                // console.log(chosenRecipe);

                // ✅ 連打防止
                btn.disabled = true;
                btn.innerText = "登録中...";

                try {
                    const response = await fetch('../../server/page/selection_server.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ recipe: chosenRecipe })
                    });


                    if (!response.ok) {
                        throw new Error('サーバーエラー');
                    }


                    const data = await response.json();

                    if (!data.success) {
                        throw new Error(data.error || '登録に失敗しました');
                    }

                    alert(`「${chosenRecipe.recipe_name}」が選ばれました！`);

                    sessionStorage.setItem('selected_recipe', JSON.stringify(chosenRecipe));

                    // ✅ 画面4へ
                    window.location.href = 'cooking.php';

                } catch (err) {
                    alert('エラー: ' + err.message);

                    // ✅ 失敗時は戻す
                    btn.disabled = false;
                    btn.innerText = "🍳 この料理にする！（採用）";
                }
            });
        });
    }
});