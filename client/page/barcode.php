<?php

$title = "バーコード登録";
$page = "barcode";

include("template/header.php");
include("template/sidebar.php");

?>

<div class="main">

    <div class="topbar">
        <h1>バーコード登録</h1>
        <div class="user"></div>
    </div>

    <div class="hero">
        <h2>📦 バーコード読み取り</h2>
        <p>商品のバーコードを読み取って食材を登録します。</p>
    </div>

    <div class="panel">

        <h2>バーコードスキャン</h2>

        <br>

        <input type="file" accept="image/*">

        <button class="btn">
            バーコードを読み取る
        </button>

    </div>

</div>

<?php include("template/footer.php"); ?>