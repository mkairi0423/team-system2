<?php
// server/page/fridge_server.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../../helpers/utils.php";

// ログインチェック
if (empty($_SESSION['user_id'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => 'ログインセッションが切れています。']);
        exit;
    } else {
        header("Location: login.php");
        exit;
    }
}

// ==========================================================================
// 🌟 非同期通信（POST）が来た場合の処理
// ==========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    $pdo = getPDO();
    $ingredientId = $_POST['ingredient_id'] ?? null;

    // ① 消費・廃棄の処理
    if (isset($_POST['status'])) {
        $status = $_POST['status'];
        if (!$ingredientId) {
            echo json_encode(['success' => false, 'error' => '必要なパラメータが不足しています。']);
            exit;
        }
        try {
            $sql = "UPDATE ingredient SET status = :status WHERE ingredient_id = :ingredient_id AND user_id = :user_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':status' => $status,
                ':ingredient_id' => $ingredientId,
                ':user_id' => $_SESSION['user_id']
            ]);
            echo json_encode(['success' => true]);
            exit;
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => 'DBエラー: ' . $e->getMessage()]);
            exit;
        }
    }

    // ② 移動の処理
    if (isset($_POST['location_id'])) {
        $locationId = $_POST['location_id'];
        if (!$ingredientId || !$locationId) {
            echo json_encode(['success' => false, 'error' => '必要なパラメータが不足しています。']);
            exit;
        }
        try {
            $stmtLoc = $pdo->prepare("SELECT is_frozen FROM storage_location WHERE location_id = :loc_id");
            $stmtLoc->execute([':loc_id' => $locationId]);
            $loc = $stmtLoc->fetch();

            if (!$loc) {
                echo json_encode(['success' => false, 'error' => '指定された保管場所が存在しません。']);
                exit;
            }

            $frozenAt = $loc['is_frozen'] == 1 ? date('Y-m-d') : null;

            $sql = "UPDATE ingredient SET storage_location_id = :location_id, frozen_at = :frozen_at WHERE ingredient_id = :ingredient_id AND user_id = :user_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':location_id' => $locationId,
                ':frozen_at' => $frozenAt,
                ':ingredient_id' => $ingredientId,
                ':user_id' => $_SESSION['user_id']
            ]);
            echo json_encode(['success' => true]);
            exit;
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => 'DBエラー: ' . $e->getMessage()]);
            exit;
        }
    }
}

// ==========================================================================
// 🏠 通常の画面表示（GET）用のデータ取得
// ==========================================================================
try {
    $pdo = getPDO();

    // 1. 食材一覧の取得 (statusが '保管中' のもののみ)
    // 1. 食材一覧の取得 (statusが '未消費' のもののみ)
    $sql = "
    SELECT i.*, l.location_name, c.category_name 
    FROM ingredient i
    JOIN storage_location l ON i.storage_location_id = l.location_id
    JOIN category c ON i.category_id = c.category_id
    WHERE i.user_id = :user_id AND i.status = '未消費'
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $ingredients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. カテゴリ一覧の取得
    $stmtCat = $pdo->query("SELECT * FROM category ORDER BY category_id ASC");
    $categories = $stmtCat->fetchAll(PDO::FETCH_ASSOC);

    // 3. 保管場所一覧の取得
    $stmtLoc = $pdo->query("SELECT * FROM storage_location ORDER BY location_id ASC");
    $locations = $stmtLoc->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("エラーが発生しました: " . $e->getMessage());
}