<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>調理中・確認画面</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/cooking.css">
</head>

<body>

    <input type="hidden" id="current-user-id" value="1">

    <div class="cooking-container">

        <h2 id="cooking-recipe-name">🍳 料理確認中...</h2>
        <p class="description-text">AIが提案した食材リストです。分量の変更wを追加をして調理を開始しましょう！</p>

        <section class="ingredient-section">
            <h3>🛒 使用する食材リスト</h3>
            <p class="sub-description">
                ※数量ボックスから実際に使う分量に修正が可能です。使わない食材は「戻す」を押してください。
            </p>

            <div id="ingredients-target-list">
            </div>
        </section>

        <hr class="section-divider">

        <section class="add-extra-section">
            <button type="button" id="accordion-toggle" class="accordion-header">
                <span>➕ 追加の食材を選択</span>
                <span class="accordion-icon">▼</span>
            </button>

            <div class="accordion-content">
                <p class="add-extra-tip">
                    お持ちの在庫食材の中から、この料理に追加したいものを選択してください。
                </p>

                <div class="add-extra-form">
                    <select id="new-food-select" class="form-select text-input">
                        <option value="">⏳ 在庫リストを読み込み中...</option>
                    </select>

                    <div class="form-row-sub">
                        <div class="counter-container">
                            <button type="button" id="btn-qty-minus" class="btn-counter">−</button>
                            <input type="number" id="new-food-qty" class="form-input qty-input" step="0.1" value="1" readonly>
                            <button type="button" id="btn-qty-plus" class="btn-counter">+</button>
                        </div>
                        <span id="new-food-unit-display" class="unit-display">個</span>
                    </div>

                    <button type="button" id="btn-add-ingredient" class="btn-add">
                        リストに追加
                    </button>
                </div>
            </div>
        </section>

        <hr class="section-divider">

        <button type="button" id="btn-cooking-complete" class="btn-complete">
            🍳 料理完了！履歴に保存
        </button>
    </div>

    <script src="../js/cooking.js"></script>
</body>

</html>