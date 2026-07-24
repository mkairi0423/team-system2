// 削除
// 食材の「消費」または「廃棄」
function deleteFood(ingredientId, actionStatus) {
    const message = actionStatus === '消費済' ? 'この食材を消費しましたか？' : 'この食材を廃棄しますか？';
    if (!confirm(message)) return;

    fetch('../../server/page/fridge_server.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `ingredient_id=${ingredientId}&status=${encodeURIComponent(actionStatus)}`
    })
    .then(res => {
        if (!res.ok) throw new Error('HTTPエラーが発生しました。');
        return res.text();
    })
    .then(text => {
        try {
            const data = JSON.parse(text);
            if (data.success) {
                // 配列（dbIngredients）から該当の食材を削除して再描画
                const index = dbIngredients.findIndex(ing => ing.ingredient_id == ingredientId);
                if (index !== -1) {
                    dbIngredients.splice(index, 1);
                }
                renderIngredients(); // 画面を更新
            } else {
                alert('処理に失敗しました: ' + data.error);
            }
        } catch (e) {
            alert('サーバーから不正なレスポンスがありました:\n' + text);
        }
    })
    .catch(err => {
        console.error(err);
        alert('通信エラーが発生しました。');
    });
}

// 冷蔵庫 → 冷凍庫
function moveToFreezer(button) {

    const row = button.closest(".food-row");

    document
        .getElementById("freezerFoods")
        .appendChild(row);

    button.textContent = "冷蔵庫へ";
    button.onclick = function () {
        moveToFridge(this);
    };

}

// 冷凍庫 → 冷蔵庫
function moveToFridge(button) {

    const row = button.closest(".food-row");

    document
        .getElementById("fridgeFoods")
        .appendChild(row);

    button.textContent = "冷凍庫へ";
    button.onclick = function () {
        moveToFreezer(this);
    };

}

// 冷蔵庫検索
const fridgeSearch = document.getElementById("fridgeSearch");

if (fridgeSearch) {

    fridgeSearch.addEventListener("keyup", function () {

        const keyword = this.value.toLowerCase();

        document
            .querySelectorAll("#fridgeFoods .food-row")
            .forEach(food => {

                const text = food.textContent.toLowerCase();

                food.style.display =
                    text.includes(keyword)
                        ? "flex"
                        : "none";
            });

    });

}

// 冷凍庫検索
const freezerSearch = document.getElementById("freezerSearch");

if (freezerSearch) {

    freezerSearch.addEventListener("keyup", function () {

        const keyword = this.value.toLowerCase();

        document
            .querySelectorAll("#freezerFoods .food-row")
            .forEach(food => {

                const text = food.textContent.toLowerCase();

                food.style.display =
                    text.includes(keyword)
                        ? "flex"
                        : "none";
            });

    });

}