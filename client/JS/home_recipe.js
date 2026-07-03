// js/home_recipe_loader.js

/**
 * localStorageから最新のAI生成レシピデータを読み込み、ホーム画面に反映する
 */
function loadSavedAiRecipe() {
    // ブラウザのキャッシュから直近のAI提案結果をサルベージ
    const savedRecipeData = localStorage.getItem('ai_recipe_result');

    const recipeNameEl = document.getElementById('ai-recipe-name');
    const recipeDescEl = document.getElementById('ai-recipe-desc');
    const featuresContainer = document.getElementById('ai-recipe-features');
    const recipeBtn = document.getElementById('ai-recipe-btn');

    // 必要とするDOM要素が揃っているか安全チェック
    if (!recipeNameEl || !recipeDescEl || !featuresContainer || !recipeBtn) {
        return;
    }

    if (savedRecipeData) {
        try {
            const result = JSON.parse(savedRecipeData);

            // プロンプト（タスクA）のJSON構造をパース
            if (result.success && result.recipes && result.recipes.length > 0) {
                const recipe = result.recipes[0];

                // 画面上のAIおすすめ枠を最新状態へ上書き
                recipeNameEl.innerText = recipe.recipe_name;
                recipeDescEl.innerText = recipe.description;

                // 特徴タグ（features）のバッジ生成
                featuresContainer.innerHTML = '';
                if (recipe.features && recipe.features.length > 0) {
                    recipe.features.forEach(feature => {
                        const span = document.createElement('span');
                        span.className = 'badge green';
                        span.style.marginRight = '5px';
                        span.style.fontSize = '12px';
                        span.innerText = feature;
                        featuresContainer.appendChild(span);
                    });
                }

                // 「レシピを見る」の挙動を設定
                recipeBtn.innerText = "レシピを見る";

                // 既存のイベントリスナーとの重複を防ぐため、一度ボタンを複製してクリーンにする
                const newRecipeBtn = recipeBtn.cloneNode(true);
                recipeBtn.parentNode.replaceChild(newRecipeBtn, recipeBtn);

                newRecipeBtn.addEventListener('click', () => {
                    window.location.href = 'selection.php';
                });
            }
        } catch (e) {
            console.error("localStorageのレシピデータ解析に失敗しました:", e);
        }
    } else {
        // まだ一度もAIレシピを作っていない場合の初期挙動
        recipeBtn.innerText = "新しくレシピを生成する";

        const newRecipeBtn = recipeBtn.cloneNode(true);
        recipeBtn.parentNode.replaceChild(newRecipeBtn, recipeBtn);

        newRecipeBtn.addEventListener('click', () => {
            window.location.href = 'recipe_conditions.php';
        });
    }
}

// 🔌 ドムの読み込みが完了したら実行
document.addEventListener('DOMContentLoaded', loadSavedAiRecipe);