<?php
require_once '../helpers/utils.php';

$pdo = getPDO();
$user_id = 1; // 仮

$sql = "SELECT * FROM cooking_now WHERE user_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

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

        <?php if (empty($items)): ?>
            <p class="empty">食材がありません</p>
        <?php else: ?>

            <?php foreach ($items as $item): ?>
                <div class="item">
                    <div class="food-name">
                        <?php echo htmlspecialchars($item['food']); ?>
                    </div>

                    <button class="return" onclick="returnItem(<?php echo $item['id']; ?>)">
                        冷蔵庫に戻す
                    </button>
                </div>
            <?php endforeach; ?>

        <?php endif; ?>

        <hr>

        <button class="complete" onclick="completeCooking()">
            🍳 料理完了
        </button>

    </div>


    <!-- 料理完了ボタン -->
    <button onclick="completeCooking()">料理完了</button>

    <script>
        // ✅ 戻す
        function returnItem(id) {
            fetch('return_item.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            })
                .then(() => location.reload());
        }

        // ✅ 料理完了
        function completeCooking() {
            fetch('complete_cooking.php', {
                method: 'POST'
            })
                .then(() => {
                    alert('料理完了！');
                    window.location.href = 'home.php'; // 画面1へ
                });
        }
    </script>

</body>

</html>