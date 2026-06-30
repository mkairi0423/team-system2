// ==================================================================================
// js/selection_AI_recipe.js （プログレスバー＆localStorage遷移完全統合版）
// ==================================================================================

/**
 * AIレシピ提案をサーバーに要求し、プログレスバーを動かすメイン関数
 */
async function handleGenerateRecipe(event) {
    // 1. 各種DOM要素の取得
    const generateBtn = event.currentTarget;
    const loadingArea = document.getElementById('loading-area');
    const progressBar = document.getElementById('recipe-progress-bar');
    const statusText = document.getElementById('loading-status');

    // 要素が存在しない場合の安全対策
    if (!loadingArea || !progressBar || !statusText) {
        console.error("プログレスバーに必要なHTML要素が見つかりません。");
        return;
    }

    // 2. 🛡️ ボタンの無効化と画面の初期化表示
    if (generateBtn) {
        generateBtn.disabled = true;
        generateBtn.style.cursor = 'not-allowed';
    }

    loadingArea.style.display = 'block';
    progressBar.style.width = '0%';
    progressBar.style.backgroundColor = '#4caf50';
    statusText.innerText = "AIシェフが冷蔵庫を確認中...";

    // 3. 画面の選択値（カスタムボタンでselectedになっているもの）を取得
    const formData = new FormData();
    const groups = ['meal_type', 'cuisine', 'flavor', 'cooking_time', 'servings'];

    groups.forEach(group => {
        const selectedBtn = document.querySelector(`.grid-options[data-group="${group}"] .option-btn.selected`);
        formData.append(group, selectedBtn ? selectedBtn.getAttribute('data-value') : '指定なし');
    });

    // 4. 🧭 疑似進捗タイマー起動（体感時間ハック）
    let progress = 0;
    const progressTimer = setInterval(() => {
        if (progress < 30) {
            progress += 10;
            statusText.innerText = "賞味期限の近い食材を厳選中...";
        } else if (progress < 60) {
            progress += 5;
            statusText.innerText = "隠し味とメニューの組み合わせを考案中...";
        } else if (progress < 95) {
            progress += 1;
            statusText.innerText = "AIシェフが全力でレシピを執筆中...";
        }
        progressBar.style.width = progress + '%';
    }, 400);

    try {
        // 5. サーバー（PHP）へ非同期通信
        const response = await fetch('../../server/page/AI_recipe_server.php', {
            method: 'POST',
            body: formData
        });

        // 6. レスポンスのチェック
        const responseText = await response.text();
        let result;
        try {
            result = JSON.parse(responseText);
        } catch (jsonError) {
            throw new Error("サーバーから不正なデータが返されました。\n" + responseText.substring(0, 200));
        }

        // 7. 通信完了！一気に100%へ持っていく
        clearInterval(progressTimer);
        progressBar.style.width = '100%';
        statusText.innerText = "レシピが完成しました！間もなく移動します...";

        if (result.success) {
            // 8. 🎉 localStorageにデータを保存して結果画面へ遷移
            setTimeout(() => {
                loadingArea.style.display = 'none';

                // データをブラウザのストレージに保存
                localStorage.setItem('ai_recipe_result', JSON.stringify(result));

                // 結果表示画面（selection.php）へ遷移
                window.location.href = 'selection.php';
            }, 1000); // 演出をしっかり見せるため1秒待って遷移

        } else {
            throw new Error(result.error || "未知のエラーが発生しました。");
        }

    } catch (error) {
        // 9. 🛑 エラー発生時のリカバリー処理
        clearInterval(progressTimer);
        progressBar.style.width = '100%';
        progressBar.style.backgroundColor = '#f44336';
        statusText.innerText = "レシピの生成に失敗しました。";

        if (generateBtn) {
            generateBtn.disabled = false;
            generateBtn.style.cursor = 'pointer';
        }

        alert("エラーが発生しました:\n" + error.message);
        console.error("Recipe Generation Error:", error);
    }
}

// 🔌 イベントリスナーの登録
document.addEventListener('DOMContentLoaded', () => {
    const generateBtn = document.getElementById('submit-to-ai');
    if (generateBtn) {
        generateBtn.addEventListener('click', handleGenerateRecipe);
    }
});