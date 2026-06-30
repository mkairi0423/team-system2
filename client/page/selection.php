<?php
//条件を選択する画面 
//selection.php
//条件検索をしてその結果のレシピを表示する画面

$title = "レシピ一覧";
$page = "selection";

include "template/header.php";
include "template/sidebar.php";

?>

<div class="container">
    <div class="header">
        <button type="button" class="btn-home" onclick="window.location.href='AI_recipe.php'">← 条件を選び直す</button>
        <h2 style="margin: 0; font-size: 1.3rem;">AIの提案レシピ</h2>
    </div>

    <p style="color: #666; margin-top: 15px;">💡 賞味期限が近い食材、冷凍保存の長いストックを優先した提案です</p>

    <div id="recipe-list-container"></div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const container = document.getElementById('recipe-list-container');
        if (!container) return;

        // localStorage からAIの生成結果データを取得
        const storedData = localStorage.getItem('ai_recipe_result');

        if (!storedData) {
            container.innerHTML = '<p style="color: #999; text-align: center; margin-top: 30px;">レシピデータが見つかりませんでした。条件を選び直してください。</p>';
            return;
        }

        try {
            const result = JSON.parse(storedData);

            if (!result.recipes || result.recipes.length === 0) {
                container.innerHTML = '<p style="color: #999; text-align: center; margin-top: 30px;">提案できるレシピが見つかりませんでした。</p>';
                return;
            }


            // データの描画処理（HTMLの組み立て）
            let html = '<div class="recipe-list">';

            result.recipes.forEach((recipe, index) => {
                html += `
        <div class="recipe-card">
            <h3 class="recipe-title">
                🍳 レシピ${index + 1}: ${escapeHtml(recipe.recipe_name)}
            </h3>

            <p class="recipe-description">
                <strong>紹介:</strong>
                ${escapeHtml(recipe.description)}
            </p>

            <div class="recipe-tags">
                ${recipe.features.map(tag => `
                    <span class="recipe-tag">
                        #${escapeHtml(tag)}
                    </span>
                `).join('')}
            </div>

            <h4 class="ingredients-title">🛒 使う食材:</h4>

            <ul class="ingredients-list">
                ${recipe.used_ingredients.map(ing => `
                    <li>
                        <strong>${escapeHtml(ing.name)}</strong>：
                        ${escapeHtml(String(ing.quantity))}
                    </li>
                `).join('')}
            </ul>

            <div class="recipe-buttons">
                <button class="recipe-btn1" onclick="viewRecipe(${index})">
                    レシピを見る
                </button>

                <button class="recipe-btn2" onclick="viewRecipe(${index})">
                    確定する
                </button>
            </div>

        </div>
    `;
            });

            html += '</div>';

            container.innerHTML = html;

        } catch (e) {
            console.error("Data parse error:", e);
            container.innerHTML = '<p style="color: #f44336; text-align: center; margin-top: 30px;">データの読み込み中にエラーが発生しました。</p>';
        }
    });

    /**
     * XSS対策用安全エスケープヘルパー
     */
    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function viewRecipe(index) {
        alert(`レシピ${index + 1}が選択されました`);
    }
</script>

<?php include "template/footer.php"; ?>