<?php
// ==========================================
// 🛠️ 【超シンプル】自前で .env を読み込む関数
// ==========================================
function loadSimpleEnv($envPath)
{
    if (!file_exists($envPath)) {
        echo json_encode(['error' => '.env ファイルが同じ場所に存在しません。']);
        exit;
    }

    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            putenv(trim($key) . '=' . trim($value));
        }
    }
}


?>