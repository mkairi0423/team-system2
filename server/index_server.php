<?php
require_once __DIR__ . "/../helpers/utils.php";

session_start();

// 1. セッションエラー・古い入力値の初期化
unset($_SESSION['name_err'], $_SESSION['pass_err'], $_SESSION['db_err']);

// POST送信以外は即座にアクセス拒否
if ($_SERVER['REQUEST_METHOD'] !== "POST") {
    header("Location: ../client/index.php");
    exit;
}

// 2. 入力値の取得と前後の余計なスペースの削除（trimはここだけで完結）
$name = trim($_POST['name'] ?? "");
$pass = trim($_POST['password'] ?? "");

// 入力値をセッションに保持（フロント側で再表示したい場合用）
$_SESSION['old'] = [
    'name' => $name
];

// 3. バリデーション（入力チェック）
// ユーザーIDのチェック
if (empty($name)) {
    $_SESSION['name_err'] = "ユーザーIDが空です";
}

// パスワードのチェック
if (empty($pass)) {
    $_SESSION['pass_err'] = "パスワードが空です";
} elseif (strlen($pass) <= 7) {
    // 空でない、かつ7文字以下の場合は文字数エラー
    $_SESSION['pass_err'] = "パスワードを8文字以上で入力してください";
}

// 1つでもエラーがあれば、この時点でフロントへ戻す
if (!empty($_SESSION['name_err']) || !empty($_SESSION['pass_err'])) {
    header("Location: ../client/index.php");
    exit;
}

try {
    // 4. データベース処理
    $pdo = getPDO();

    // nameカラムでユーザーを検索（バインド変数名は :name に統一）
    $sql = "SELECT * FROM users WHERE name = :name LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(":name", $name, PDO::PARAM_STR);
    $stmt->execute();

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // ユーザーが存在しない場合
    if (!$user) {
        // セキュリティを考慮し、IDとパスワードのどちらが間違っているかは明かさない
        $_SESSION['pass_err'] = "ユーザーIDまたはパスワードが間違っています。";
        header("Location: ../client/index.php");
        exit;
    }

    // 5. パスワードの照合（DB内のハッシュ値と、入力された生パスワードを比較）
    if (!password_verify($pass, $user['password'])) {
        $_SESSION['pass_err'] = "ユーザーIDまたはパスワードが間違っています。";
        header("Location: ../client/index.php");
        exit;
    }

    // 6. ログイン成功処理
    // セッションにユーザー情報を保存（DBのカラム名に正確に合わせる）
    $_SESSION['user'] = [
        'uid'   => $user['uid'],
        'name'  => $user['name'],   // name_id から実際のカラム名 name に修正
        // 'email' => $user['email']
    ];

    // 不要になった「古い入力値」のセッションをクリア
    unset($_SESSION['old']);

    // ホーム画面へリダイレクト
    header("Location: ../client/page/home.php");
    exit;

} catch (PDOException $e) {
    // 7. データベースエラー時の安全な例外処理
    error_log("DB Error: " . $e->getMessage()); // ログにエラー詳細を記録
    $_SESSION['db_err'] = "システムエラーが発生しました。";
    header("Location: ../client/index.php");
    exit;
}