// 削除
function deleteFood(button) {

    if (confirm("この食材を削除しますか？")) {
        button.closest(".food-row").remove();
    }

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