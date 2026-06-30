<?php
// ==================================================================================
// helpers/gemini_api.php （.env読み込み ＆ 頑丈なGemini API通信関数 統合版）
// ==================================================================================

// ==========================================
// 🛠️ 【超シンプル】自前で .env を読み込む関数
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
            // クォーテーションを綺麗に剥ぎ取る
            $value = trim(trim($value), '"\'');

            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

// 関数を実行する（1つ上の階層の .env を見に行く）
loadSimpleEnv(__DIR__ . '/../.env');


// ==================================================================================
// 🔥 【新設】Gemini API へリクエストを送る共通関数（cURLエラーキャッチ付き）
// ==================================================================================
if (!function_exists('call_gemini_api')) {
    /**
     * Gemini API (v1beta) に画像とプロンプトを送信する
     * * @param string $base64_image 画像のBase64文字列（プレフィックス除去済みのもの）
     * @param string $prompt AIへの指示文（プロンプト）
     * @param string $api_key GeminiのAPIキー
     * @return string|false AIからの応答テキスト、または失敗時にfalse
     */
    function call_gemini_api($base64_image, $prompt, $api_key)
    {
        if (empty($api_key)) {
            return false;
        }

        // 使用するモデル（最新の軽量・高速モデル）
        $model = "gemini-1.5-flash";
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$api_key}";

        // Gemini API が要求するPayload構造に整形
        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                        [
                            'inlineData' => [
                                'mimeType' => 'image/jpeg', // jpeg/png両対応
                                'data' => $base64_image
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        // 🛑 【超重要】Windowsのローカル環境（XAMPP等）でのSSL証明書エラー（通信が空になる問題）を回避する設定
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        // タイムアウト設定（画像解析は時間がかかるため長めに確保）
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        $response = curl_exec($ch);

        // 通信自体がエラーを起こした場合のデバッグログ仕込み
        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
            // ログや画面に出力できるように一時的にバックバッファに逃がす
            trigger_error("Gemini cURL Error: " . $error_msg, E_USER_WARNING);
            curl_close($ch);
            return false;
        }

        curl_close($ch);

        // 応答データからAIの純粋なテキスト発言部分だけを抽出
        $res_array = json_decode($response, true);
        if (isset($res_array['candidates'][0]['content']['parts'][0]['text'])) {
            return $res_array['candidates'][0]['content']['parts'][0]['text'];
        }

        // APIからエラーメッセージが返ってきている場合はそれをそのまま返す（原因特定のため）
        if (isset($res_array['error']['message'])) {
            return "API_ERROR: " . $res_array['error']['message'];
        }

        return false;
    }
}


/**
 * テキストのキーワードから食材をJSON配列で取得する関数
 */
function getNeededIngredientsFromAI($keyword) {
    // 1. 在庫食材の名前リストを取得してプロンプトに埋め込む
    $pdo = getPDO();
    $stmt = $pdo->query("SELECT food_name FROM ingredient");
    $stockNames = implode("、", $stmt->fetchAll(PDO::FETCH_COLUMN));

    $api_key = $_ENV['GEMINI_API_KEY'] ?? '';
    $model = "gemini-2.5-flash"; // もしくは 2.0-flash など
    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$api_key}";

    // プロンプトを強化：在庫リストを渡して、そこに含まれる名称で出力させる
    $prompt = "「{$keyword}」を作るのに必要な食材を答えてください。
    ただし、以下の【在庫リスト】に存在する食材は、その名前のまま出力してください。
    在庫リスト: {$stockNames}
    JSON配列形式（例: [\"豚ひき肉\", \"卵\"]）で回答し、それ以外の文字は一切禁止。";

    $payload = ['contents' => [['parts' => [['text' => $prompt]]]]];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        return ["API_ERROR: HTTP $httpCode, 応答: $response"];
    }

    $res_array = json_decode($response, true);
    $text = $res_array['candidates'][0]['content']['parts'][0]['text'] ?? '';
    $text = preg_replace('/^```json\s*|\s*```$/', '', trim($text));
    
    $decoded = json_decode($text, true);
    return is_array($decoded) ? $decoded : ["PARSE_ERROR: " . $text];
}