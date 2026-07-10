<?php
// ページ識別子を設定（これでサイドバーの「料理履歴」が青く光ります）
$title = "料理履歴";
$page = "history";

include "template/header.php";
include "template/sidebar.php";

session_start();
require_once __DIR__ . "/../../helpers/utils.php";
require_once __DIR__ . "/../../helpers/def.php";
hasUserId();
?>

<div class="main">

    <div class="topbar">
        <h1>料理履歴</h1>
    </div>

    <div id="history-list-container" class="history-grid">
        <div class="card loading-card">
            <p>履歴データを読み込み中...</p>
        </div>
    </div>

</div>

<!-- <script src="../js/history.js"></script> -->

<?php include "template/footer.php"; ?>