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

            <div class="form-group">
                <label>現在のパスワード</label>
                <input type="password">
            </div>

            <div class="form-group">
                <label>新しいパスワード</label>
                <input type="password">
            </div>

            <button class="btn-primary">変更する</button>
        </div>

        <!-- デザイン -->
        <div class="setting-card">
            <h2>🎨 デザイン</h2>

            <div class="setting-row">
                <span>ダークモード</span>
                <label class="switch">
                    <input type="checkbox">
                    <span class="slider"></span>
                </label>
            </div>

            <div class="form-group">
                <label>背景テーマ</label>
                <select>
                    <option>デフォルト</option>
                    <option>ブルー</option>
                    <option>グリーン</option>
                    <option>ダーク</option>
                </select>
            </div>
        </div>

        <!-- 通知 -->
        <div class="setting-card">
            <h2>🔔 通知</h2>

            <div class="setting-row">
                <span>賞味期限通知</span>
                <label class="switch">
                    <input type="checkbox" checked>
                    <span class="slider"></span>
                </label>
            </div>

            <div class="setting-row">
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
            <button class="btn-secondary">
                ログアウト
            </button>

            <button class="btn-danger">
                アカウント削除
            </button>
        </div>
    </div>

</div>

<?php include "template/footer.php"; ?>