<?php

$title = "設定";
$page = "settings";

include "template/header.php";
include "template/sidebar.php";

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

                <input type="text" name="username"
                    value="<?php echo htmlspecialchars($_SESSION['user']['name'] ?? 'guest_user'); ?>"
                    autocomplete="username" style="display:none;">

                <div class="form-group">
                    <label for="current-password">現在のパスワード</label>
                    <input type="password" id="current-password" name="current_password" autocomplete="current-password"
                        required>
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
                <button class="btn-secondary">ログアウト</button>
            </a>

            <button type="button" class="btn-danger"
                onclick="document.getElementById('deleteDialog').showModal()">アカウント削除</button>
        </div>
    </div>

    <dialog id="deleteDialog">
    <h3>⚠️ 最終確認 ⚠️</h3>
    <p>本当にアカウントを削除しますか？<br>この操作は取り消せません。</p>
    <div style="display: flex; gap: 10px; justify-content: center; margin-top: 20px;">
        <button type="button" class="btn-secondary"
            onclick="document.getElementById('deleteDialog').close()">キャンセル</button>

        <form action="../../server/page/user_delete_server.php" method="POST">
            <?php
            // フロント側のセッションからユーザーIDを取得して、隠し入力にセット
            $current_user_id = $_SESSION['user']['user_id'] ?? $_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? '';
            ?>
            <input type="hidden" name="delete_user_id" value="<?php echo htmlspecialchars($current_user_id, ENT_QUOTES, 'UTF-8'); ?>">
            
            <button type="submit" class="btn-danger">本当に削除する</button>
        </form>
    </div>
</dialog>

</div>

<?php include "template/footer.php"; ?>