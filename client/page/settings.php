<?php

$title = "設定";
$page = "settings";

include "template/header.php";
include "template/sidebar.php";

session_start();
require_once __DIR__ . "/../../helpers/utils.php";
require_once __DIR__ . "/../../helpers/def.php";
hasUserId();

?>

<div class="main">

    <div class="settings-header">
        <h1>⚙️ 設定</h1>
        <p>アカウント情報やアプリの表示設定を管理します</p>
    </div>

    <div class="settings-grid">

        <!-- アカウント -->
        <div class="setting-card">
            <h2>👤 アカウント</h2>

            <div class="form-group">
                <label>ユーザー名</label>
                <input type="text" placeholder="ユーザー名">
            </div>

            <div class="form-group">
                <label>メールアドレス</label>
                <input type="email" placeholder="sample@example.com">
            </div>

            <button class="btn-primary">保存</button>
        </div>

        <!-- セキュリティ -->
        <div class="setting-card">
            <h2>🔒 セキュリティ</h2>

            <form id="form-password-change" action="" method="POST">

                <input type="text"
                    name="username"
                    value="<?php echo htmlspecialchars($_SESSION['user']['name'] ?? 'guest_user'); ?>"
                    autocomplete="username"
                    style="display:none;">

                <div class="form-group">
                    <label for="current-password">現在のパスワード</label>
                    <input type="password" id="current-password" name="current_password" autocomplete="current-password" required>
                </div>

                <div class="form-group">
                    <label for="new-password">新しいパスワード</label>
                    <input type="password" id="new-password" name="new_password" autocomplete="new-password" required>
                </div>

                <button type="submit" class="btn-primary">変更する</button>

            </form>
        </div>

        <!-- デザイン -->
        <div class="setting-card">
            <h2>🎨 デザイン</h2>

            <div class="setting-row">
                <span>ダークモード</span>
                <label class="switch">
                    <input type="checkbox" id="darkModeToggle">
                    <span class="slider"></span>
                </label>
            </div>
        </div>

        <!-- 通知 -->
        <div class="setting-card">
            <h2>🔔 通知</h2>

            <div class="setting-row form-group">
                <span>賞味期限通知</span>
                <label class="switch">
                    <input type="checkbox" checked>
                    <span class="slider"></span>
                </label>
            </div>

            <div class="setting-row form-group">
                <span>在庫不足通知</span>
                <label class="switch">
                    <input type="checkbox" checked>
                    <span class="slider"></span>
                </label>
            </div>
            
        </div>
    </div>

    <div class="setting-card danger-card">
        <h2>⚠️ その他</h2>

        <div class="danger-buttons">
            <a href="../../server/page/logout_server.php">
                <button class="btn-secondary">
                    ログアウト
                </button>
            </a>

            <button class="btn-danger">
                アカウント削除
            </button>
        </div>
    </div>

</div>

<?php include "template/footer.php"; ?>