<?php
// helpers/gemini_api.php

// ==========================================
// 🛠️ 【超シンプル】自前で .env を読み込む関数（改良版）
// ==========================================
function loadSimpleEnv($envPath)
{
    if (!file_exists($envPath)) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success' => false,
            'error' => '.env ファイルが見つかりません。指定されたパス: ' . $envPath
        ]);
        exit;
    }

    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // 💡 putenvに頼らず、確実にPHPのグローバル配列に保存する
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

// 関数を実行する（1つ上の階層の .env を見に行く）
loadSimpleEnv(__DIR__ . '/../.env');