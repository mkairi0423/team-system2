<!DOCTYPE html>
<html lang="ja">

<head>
    <link rel="stylesheet" href="../css/cooking.css">
    <meta charset="UTF-8">
    <title>調理中</title>
</head>

<body>

    <div class="container">
        <h2 class="title">🍳 使用する食材</h2>
        <hr>

        <!-- ✅ ここにJSで食材が入る -->
        <div id="food-list"></div>

        <hr>

        <button class="complete" onclick="completeCooking()">
            🍳 料理完了
        </button>
    </div>


    <script>
        // ✅ 前の画面で保存したレシピを取得
        const recipe = JSON.parse(sessionStorage.getItem('selected_recipe'));

        const container = document.getElementById('food-list');

        if (!recipe) {
            container.innerHTML = "<p class='empty'>食材がありません</p>";
        } else {

            const ingredients = recipe.used_ingredients ?? [];

            ingredients.forEach(item => {

                // ✅ オブジェクト → 文字列変換
                const name = (typeof item === 'object')
                    ? item.name || item.food || Object.values(item)[0]
                    : item;

                const div = document.createElement('div');
                div.className = 'item';

                div.innerHTML = `
                    <div class="food-name">${name}</div>
                `;

                container.appendChild(div);
            });
        }


        // ✅ 料理完了（ここがDB更新）
        function completeCooking() {

            if (!recipe) {
                alert("データがありません");
                return;
            }

            const ingredients = recipe.used_ingredients ?? [];

            const names = ingredients.map(item => {
                if (typeof item === 'object') {
                    return item.name || item.food || Object.values(item)[0];
                }
                return item;
            });

            fetch('complete_cooking.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ingredients: names })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('料理完了！');

                    // ✅ データ消す
                    sessionStorage.removeItem('selected_recipe');

                    window.location.href = 'home.php';
                } else {
                    alert('エラー: ' + data.error);
                }
            });
        }

    </script>

</body>
</html>