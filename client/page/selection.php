<?php
// 条件を選択する画面 
// selection.php
// 条件検索をしてその結果のレシピを表示する画面

$title = "レシピ一覧";
$page = "selection";

include "template/header.php";
include "template/sidebar.php";

session_start();
require_once __DIR__ . "/../../helpers/utils.php";
require_once __DIR__ . "/../../helpers/def.php";
hasUserId();
?>

<style>
    .recipe-status-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: bold;
        margin-bottom: 10px;
    }

    .badge-perfect {
        background-color: #dcfce7;
        color: #166534;
        border: 1px solid #bbf7d0;
    }

    .badge-buy {
        background-color: #fef9c3;
        color: #854d0e;
        border: 1px solid #fef08a;
    }

    .badge-expiry {
        background-color: #fee2e2;
        color: #991b1b;
        border: 1px solid #fca5a5;
    }

    .ing-status {
        display: inline-block;
        font-size: 0.75rem;
        padding: 2px 6px;
        border-radius: 4px;
        margin-left: 6px;
        font-weight: bold;
    }

    .status-stock {
        background-color: #e2e8f0;
        color: #475569;
    }

    .status-needed {
        background-color: #fef2f2;
        color: #ef4444;
        border: 1px solid #fee2e2;
    }
</style>

<div class="container">
    <div class="header">
        <button type="button" class="btn-home" onclick="window.location.href='AI_recipe.php'">← 条件を選び直す</button>
        <h2 style="margin: 0; font-size: 1.3rem;">AIの提案レシピ</h2>
    </div>

    <p style="color: #666; margin-top: 15px;">💡 あなたの冷蔵庫の在庫状況に合わせたハイブリッド提案です</p>

    <div id="recipe-list-container"></div>
</div>

<!---------------------------------------------------------------------
ここでセッションからuser_idを取得してきてサーバー側に渡している
--------------------------------------------------------------------->

<input type="hidden" id="current-user-id" value="<?= htmlspecialchars($_SESSION['user_id'], ENT_QUOTES) ?>">

<!-- ---------------------------------------------------------------- 
-------------------------------------------------------------------->

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
                const encodedName = encodeURIComponent(recipe.recipe_name);
                const recipeUrl = `https://cookpad.com/search/${encodedName}`;

                // 💡 プロンプトの仕様（0番目:ぴったり, 1番目:買い足し, 2番目:期限消費）に合わせてレシピバッジを切り替える
                let statusBadgeHtml = '';
                if (index === 0) {
                    statusBadgeHtml = '<span class="recipe-status-badge badge-perfect">✨ 食材がぴったり！今すぐ作れる</span>';
                } else if (index === 1) {
                    statusBadgeHtml = '<span class="recipe-status-badge badge-buy">🛒 あとちょっと！買い足しが必要です</span>';
                } else if (index === 2) {
                    statusBadgeHtml = '<span class="recipe-status-badge badge-expiry">🚨 ロス削減！期限が近い食材を消費</span>';
                }

                html += `
        <div class="recipe-card" style="position: relative;">
            
            ${statusBadgeHtml}

            <h3 class="recipe-title" style="margin-top: 5px;">
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
                ${recipe.used_ingredients.map(ing => {
                    // 💡 食材ごとの「在庫あり」「要買い足し」ラベルの切り替え
                    const isNeeded = ing.status === '要買い足し' || ing.id === null;
                    const labelClass = isNeeded ? 'status-needed' : 'status-stock';
                    const labelText = isNeeded ? '⚠️不足' : '庫内あり';
                    
                    return `
                    <li style="${isNeeded ? 'color: #c2410c; font-weight: 500;' : ''}">
                        <strong>${escapeHtml(ing.name)}</strong>：
                        ${escapeHtml(String(ing.quantity))}
                        <span class="ing-status ${labelClass}">${labelText}</span>
                    </li>
                    `;
                }).join('')}
            </ul>

            <div class="recipe-buttons">
                <button class="recipe-btn1" onclick="viewRecipe('${escapeHtml(recipeUrl)}')">
                    レシピを見る
                </button>

                <button class="recipe-btn2" id="btn-confirm-${index}" onclick="actionConfirm(this, ${index})">
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

    /**
     * レシピを見るボタンの処理
     */
    function viewRecipe(url) {
        if (!url) return;
        window.open(url, '_blank', 'noopener,noreferrer');
    }

    /**
     * 確定するボタンの処理
     */
    async function actionConfirm(buttonElement, recipeIndex) {
        // ローカルストレージから最新の提案データを安全に再取得
        const storedData = localStorage.getItem('ai_recipe_result');
        if (!storedData) return alert("エラー：レシピデータが見つかりません。");

        const result = JSON.parse(storedData);
        const recipe = result.recipes[recipeIndex];
        if (!recipe) return alert("エラー：対象のレシピが見つかりません。");

        //調理開始のalert
        // const dishName = recipe.recipe_name;
        // if (!confirm(`「${dishName}」の調理を開始しますか？\n必要な食材を調理リストへ移動します。`)) return;

        const API_URL = '../../server/page/cooking_server.php';
        const userIdEl = document.getElementById("current-user-id");
        const userId = userIdEl ? userIdEl.value : 1;

        try {
            buttonElement.disabled = true;
            buttonElement.innerText = "⏳ 処理中...";

            // 💡 修正＆最適化ポイント：
            // AIが算出した「status（要買い足し）」や「id（null）」を保持したまま調理開始（start）APIへ一発で投げます！
            // これにより、再度サーバーでAIを呼び出す無駄な通信を削減し、一瞬で次の画面へ遷移します。
            const cookingItems = recipe.used_ingredients.map(ing => {
                return {
                    id: ing.id, // 在庫ありなら数値、買い足しなら null
                    food: ing.name, // 食材名
                    use_quantity: ing.quantity // 必要な数量
                };
            });

            // 調理開始リクエストを送信
            const startResponse = await fetch(`${API_URL}?action=start`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    user_id: userId,
                    items: cookingItems
                })
            });

            if (!startResponse.ok) {
                throw new Error(`調理開始の通信に失敗しました (Status: ${startResponse.status})`);
            }

            const startResult = await startResponse.json();

            if (!startResult.success) {
                throw new Error(startResult.message || '調理の開始登録に失敗しました。');
            }

            // 登録が成功したら次の「調理中・確認画面」へ遷移
            const encodedDish = encodeURIComponent(recipe.recipe_name); // 料理名をエンコード
            window.location.href = `cooking.php?dish=${encodedDish}`; // パラメータを付与して遷移

        } catch (error) {
            console.error('調理開始エラー:', error);
            alert('調理開始エラー: ' + error.message);
            buttonElement.disabled = false;
            buttonElement.innerText = "確定する";
        }
    }
</script>

<?php include "template/footer.php"; ?>