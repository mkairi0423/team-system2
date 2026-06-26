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
            let html = '<div class="recipe-list" style="display: flex; flex-direction: column; gap: 20px; margin-top: 15px;">';

            result.recipes.forEach((recipe, index) => {
                html += `
                <div class="recipe-card" style="border: 1px solid #e0e0e0; padding: 20px; border-radius: 12px; background-color: #fff; box-shadow: 0 4px 6px rgba(0,0,0,0.05); transition: transform 0.2s;">
                    <h3 style="margin-top: 0; color: #333; font-size: 1.2rem; border-bottom: 2px solid #f0f0f0; padding-bottom: 8px;">
                        🍳 レシピ${index + 1}: ${escapeHtml(recipe.recipe_name)}
                    </h3>
                    <p style="color: #555; line-height: 1.6; margin: 10px 0;">
                        <strong>紹介:</strong> ${escapeHtml(recipe.description)}
                    </p>
                    <div class="tags" style="margin: 10px 0; display: flex; flex-wrap: wrap; gap: 5px;">
                        ${recipe.features.map(tag => `
                            <span style="background: #e8f5e9; color: #2e7d32; padding: 4px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: bold;">
                                #${escapeHtml(tag)}
                            </span>
                        `).join('')}
                    </div>
                    <h4 style="margin: 15px 0 5px 0; color: #444; font-size: 1rem;">🛒 使う食材:</h4>
                    <ul style="padding-left: 20px; margin: 0; color: #666; line-height: 1.5;">
                        ${recipe.used_ingredients.map(ing => `
                            <li style="margin-bottom: 4px;">
                                <strong style="color: #333;">${escapeHtml(ing.name)}</strong>: ${escapeHtml(String(ing.quantity))}
                            </li>
                        `).join('')}
                    </ul>
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
</script>

<?php include "template/footer.php"; ?>