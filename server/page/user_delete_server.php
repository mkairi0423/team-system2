<?php
// ==================================================================================
// server/page/user_delete_server.php （セッション構造最適化版）
// ==================================================================================

require_once __DIR__ . "/../../helpers/utils.php";
require_once __DIR__ . "/../DB_function/user_register_DB.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

//  1. POSTリクエスト以外は弾く
if ($_SERVER['REQUEST_METHOD'] !== "POST") {
    echo "エラー: 正しくボタンが押されていません。";
    exit;
}

//  2. セッションからあらゆる可能性を考慮してユーザーIDを取得
$user_id = $_SESSION['user_id'] ?? $_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? $_POST['delete_user_id'] ?? null;

if (!$user_id) {
    echo "<h2>❌ エラー: 削除対象のユーザーIDが特定できませんでした</h2>";
    echo "セッションデータ: <pre>";
    print_r($_SESSION);
    echo "</pre>";
    exit;
}

try {
    //  3. データベース接続の確保
    if (!isset($pdo) && isset($db)) {
        $pdo = $db;
    }

    if (!isset($pdo)) {
        $pdo = new PDO(
            'mysql:host=localhost;dbname=team_system2;charset=utf8mb4',
            'root',
            'root',
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    }

    // トランザクション開始
    $pdo->beginTransaction();

    //  4. 子テーブル（関連データ）の削除
    $stmt_ing = $pdo->prepare("DELETE FROM ingredient WHERE user_id = :user_id");
    $stmt_ing->execute([':user_id' => $user_id]);

    //  5. 親テーブル（ユーザー自身）の削除
    // エラーの原因だった「OR id = :user_id」を削除し、正しい「user_id」だけにします
    $stmt_user = $pdo->prepare("DELETE FROM user WHERE user_id = :user_id");
    $stmt_user->execute([':user_id' => $user_id]);

    // 本当にデータが消されたか確認
    $deleted_rows = $stmt_user->rowCount();

    if ($deleted_rows === 0) {
        $pdo->rollBack();
        echo "<h2>❌ アカウント削除に失敗しました</h2>";
        echo "理由: データベースに ID <strong>" . htmlspecialchars($user_id) . "</strong> のユーザーが見つかりませんでした。";
        echo "<br>userテーブルの主キー（カラム名）が user_id でも id でもない可能性があります。";
        exit;
    }

    // コミットして確定
    $pdo->commit();

    //  6. セッションを完全に破壊
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    session_destroy();

    //  7. 削除成功後、ログイン画面へ
    header("Location: ../../client/index.php");
    exit;

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "<h2>❌ アカウント削除中にエラーが発生しました</h2>";
    echo "<strong>エラー詳細:</strong> " . htmlspecialchars($e->getMessage());
    exit;
}