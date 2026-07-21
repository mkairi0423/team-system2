<?php
// ===================================================
// page/food_register.php （完全版UI・自動解析化に合わせたスッキリ修正版）
// ===================================================
session_start();

$title = "食材登録";
$page = "food";

include("template/header.php");
include("template/sidebar.php");

require_once __DIR__ . "/../../helpers/utils.php";
require_once __DIR__ . "/../../helpers/def.php";
hasUserId();

// 手入力の初期値として「今日の日付」を設定
$today = date('Y-m-d');
?>

<div class="main">

    <div class="topbar">
        <h1>食材登録</h1>
    </div>

    <div class="grid">
        <div class="card">
            <h3>📷 レシート食材重量逆算スキャナー</h3>
            <br>
            <p>
                レシートの写真または画像を選択すると、<br>
                自動的にAIによる解析がスタートします。
            </p>
            <br>
            <label for="receipt-file" class="file-label" style="background: #3498db; color: white; padding: 10px 15px; border-radius: 5px; cursor: pointer; display: inline-block; font-weight: bold;">
                📸 写真を撮る / 画像を選択
            </label>
            <input
                type="file"
                accept="image/*"
                capture="environment"
                id="receipt-file"
                style="display: none;">


            <div id="result" style="margin-top: 15px; background: #f8f9fa; padding: 15px; border-radius: 8px; border: 1px solid #ddd; min-height: 60px;">
                ここに解析結果（食材名・重量・期限・保存場所など）が表示されます。
            </div>
        </div>

        <div class="card">
            <h3>⌨️ 手入力登録</h3>
            <br>

            <label style="font-weight: bold; font-size: 0.9em;">食材名</label>
            <input
                type="text"
                id="foodName"
                placeholder="例: きゅうり"
                class="search-box"
                style="width: 100%; padding: 8px; margin-bottom: 12px; border-radius: 4px; border: 1px solid #ccc;">

            <label style="font-weight: bold; font-size: 0.9em;">数量 (g または 個)</label>
            <input
                type="number"
                id="foodAmount"
                placeholder="例: 150"
                class="search-box"
                style="width: 100%; padding: 8px; margin-bottom: 12px; border-radius: 4px; border: 1px solid #ccc;">

            <label style="font-weight: bold; font-size: 0.9em;">カテゴリ</label>
            <select
                id="foodCategory"
                class="search-box"
                style="width: 100%; padding: 8px; margin-bottom: 12px; border-radius: 4px; border: 1px solid #ccc;">
                <option value="1">肉・魚類</option>
                <option value="2">野菜・果物類</option>
                <option value="3">卵・乳製品・大豆</option>
                <option value="4">その他</option>
            </select>

            <label style="font-weight: bold; font-size: 0.9em;">保存場所</label>
            <select
                id="manualStoragePlace"
                class="search-box"
                style="width: 100%; padding: 8px; margin-bottom: 12px; border-radius: 4px; border: 1px solid #ccc;">
                <option value="冷蔵庫">❄️ 冷蔵庫</option>
                <option value="冷凍庫">🥶 冷凍庫</option>
                <option value="常温・パントリー">📦 常温・パントリー</option>
                <option value="野菜室">🥬 野菜室</option>
            </select>

            <label style="font-weight: bold; font-size: 0.9em;">期限の種類</label>
            <select
                id="manualTermType"
                class="search-box"
                style="width: 100%; padding: 8px; margin-bottom: 12px; border-radius: 4px; border: 1px solid #ccc; font-weight: bold;">
                <option value="best_before" style="color: #2ece7d;">賞味期限</option>
                <option value="use_by" style="color: #ff4d4d;">消費期限</option>
            </select>

            <label style="font-weight: bold; font-size: 0.9em;">期限日</label>
            <input
                type="date"
                id="manualExpiryDate"
                value="<?php echo $today; ?>"
                class="search-box"
                style="width: 100%; padding: 8px; margin-bottom: 15px; border-radius: 4px; border: 1px solid #ccc;">

            <button
                class="btn"
                onclick="addFood()"
                style="width: 100%; background: #2ece7d; color: white; padding: 10px; border: none; border-radius: 5px; font-weight: bold; cursor: pointer;">
                食材を登録する
            </button>
        </div>
    </div>

    <div class="topbar">
        <h1>バーコード登録</h1>
    </div>

    <div class="hero">
        <h2>📦 バーコード読み取り</h2>
        <p>商品のバーコードをカメラで読み取り、食材を登録します。</p>
    </div>

    <div class="panel">

        <h2>バーコードスキャン</h2>

        <!-- カメラ映像 -->
        <div class="camera-area">
            <video id="video" autoplay playsinline></video>
        </div>

        <p class="guide">
            バーコードを枠内に合わせてください。
        </p>

        <button type="button" class="btn" id="startScan">
            📷 スキャン開始
        </button>

        <button type="button" class="btn btn-secondary" id="stopScan">
            停止
        </button>

        <div class="result">

            <h3>読み取り結果</h3>

            <p id="barcodeResult">
                まだ読み取っていません
            </p>


            <!-- 商品情報表示 -->
            <div id="productResult">

            </div>


            <!-- PHPへ送るJANコード -->
            <input type="hidden" id="barcode" name="barcode">


            <br>


            <button type="button" class="btn" id="registerBarcode">
                登録する
            </button>


        </div>

    </div>

    <!-- ZXing -->
    <script src="https://unpkg.com/@zxing/library@latest"></script>

    <!-- バーコード読み取り -->
    <script src="../js/barcode.js"></script>

    <!-- DB登録処理 -->
    <script src="../js/food_register.js"></script>

    <?php include("template/footer.php"); ?>