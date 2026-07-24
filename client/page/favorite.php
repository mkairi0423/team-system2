<?php
// favorite.php
$title = "お気に入りレシピ";
$page = "favorite";

include "template/header.php";
include "template/sidebar.php";

session_start();
require_once __DIR__ . "/../../helpers/utils.php";
require_once __DIR__ . "/../../helpers/def.php";
hasUserId();
?>

<div class="main">

    <div class="topbar">
        <h1>お気に入りレシピ</h1>
    </div>

    <div id="recipe-container" class="history-grid">
        <div class="card loading-card" id="loading-element">
            <p>データを読み込み中...</p>
        </div>
    </div>

</div>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        const container = document.getElementById("recipe-container");

        // サーバー側API（favorite_server.php）からデータ取得
        fetch("/team-system2/server/page/favorite_server.php")
            .then(response => {
                if (!response.ok) throw new Error("ネットワークエラー");
                return response.json();
            })
            .then(recipes => {
                // 💡 通信が成功したら、一旦コンテナの中身（ローディング表示）を完全に空っぽにします
                container.innerHTML = "";

                // レシピが1件もない（空の配列 `[]` が返ってきた）場合
                if (!recipes || recipes.length === 0) {
                    container.innerHTML = `
                        <div class="card loading-card" style="border-style: dashed; width: 100%; grid-column: 1 / -1;">
                            <p>お気に入り登録されたレシピはありません</p>
                        </div>
                    `;
                    return;
                }

                // レシピが存在する場合、カードを生成して挿入
                recipes.forEach(recipe => {
                    let ingredientsHtml = "";
                    if (recipe.ingredients && recipe.ingredients.length > 0) {
                        recipe.ingredients.forEach(ing => {
                            ingredientsHtml += `
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 6px 12px; border-radius: 8px; margin-bottom: 4px; font-size: 14px; background: #f8fafc;">
                                    <span style="color: #334155;">
                                        <span style="font-size: 11px; color: #94a3b8; margin-right: 4px;">[${ing.category_name || '食材'}]</span>${ing.food_name_hint}
                                    </span>
                                    <span style="font-size: 11px; background: #eff6ff; color: #2563eb; padding: 2px 8px; border-radius: 9999px; font-weight: bold;">
                                        ${ing.quantity ?? ''}${ing.unit}
                                    </span>
                                </div>
                            `;
                        });
                    } else {
                        ingredientsHtml = "<p style='font-size: 12px; color: #94a3b8; font-style: italic;'>登録された食材はありません</p>";
                    }

                    const memoHtml = recipe.memo 
                        ? `<p style="font-size: 14px; color: #475569; background: #fef3c7; border: 1px solid #fde68a; padding: 10px; border-radius: 8px; margin-bottom: 12px; font-style: italic;">💡 ${recipe.memo}</p>` 
                        : "";

                    const urlHtml = recipe.recipe_url 
                        ? `<a href="${recipe.recipe_url}" target="_blank" rel="noopener noreferrer" style="color: #3b82f6; text-decoration: underline;">🔗 参考元のURLを開く</a>` 
                        : "";

                    const formattedDate = new Date(recipe.created_at).toLocaleDateString('ja-JP');

                    const cardHtml = `
                        <div class="card" style="position: relative; padding: 20px; display: flex; flex-direction: column; justify-content: space-between;">
                            <button class="favorite-btn" style="position: absolute; top: 16px; right: 16px; color: #fbbf24; font-size: 20px;" title="お気に入り解除">★</button>
                            <div>
                                <h3 style="font-size: 18px; font-weight: bold; color: #1e293b; margin-bottom: 8px; padding-right: 20px;">${recipe.recipe_name}</h3>
                                ${memoHtml}
                                <div style="margin-bottom: 16px;">
                                    <h4 style="font-size: 12px; font-weight: bold; color: #94a3b8; letter-spacing: 0.05em; text-transform: uppercase; margin-bottom: 8px;">必要な食材</h4>
                                    <div>${ingredientsHtml}</div>
                                </div>
                            </div>
                            <div style="border-top: 1px solid #f1f5f9; padding-top: 12px; margin-top: 8px; display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: #94a3b8;">
                                <div>${urlHtml}</div>
                                <span>${formattedDate} 登録</span>
                            </div>
                        </div>
                    `;
                    container.insertAdjacentHTML("beforeend", cardHtml);
                });
            })
            .catch(error => {
                console.error("Error:", error);
                container.innerHTML = `
                    <div class="card loading-card" style="width: 100%; grid-column: 1 / -1;">
                        <p style="color: #ef4444; font-weight: bold;">⚠️ データの読み込みに失敗しました。</p>
                    </div>
                `;
            });
    });
</script>

<?php include "template/footer.php"; ?>