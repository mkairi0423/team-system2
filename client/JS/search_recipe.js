// ==================================================================================
// js/search_recipe.js （food_scan.js 設計思想・最新API 適合版）
// ==================================================================================

(function () {
    "use strict";

    // ==========================================
    // 1. 設定の集約
    // ==========================================
    const CONFIG = {
        // 💡 先ほど設定したPHPファイルへのパス
        API_ENDPOINT: "/team-system2/server/page/get_recipe_ingredients.php",
    };

    // ==========================================
    // 2. DOM要素の初期化と検証
    // ==========================================
    const searchBtn = document.getElementById("search-recipe-btn");     // 検索ボタン
    const keywordInput = document.getElementById("recipe-keyword");     // 入力欄
    const resultDiv = document.getElementById("recipe-result-container"); // 結果表示エリア

    // 💡 画面にユーザーIDを保持している要素（無ければセッションやinput等から取得してください）
    const userIdInput = document.getElementById("current-user-id") || { value: 1 };

    if (!searchBtn || !keywordInput || !resultDiv) return;

    searchBtn.addEventListener("click", handleSearch);
    // エンターキーでも検索できるように設定
    keywordInput.addEventListener("keypress", function (e) {
        if (e.key === "Enter") handleSearch();
    });

    // ==========================================
    // 3. イベントハンドラー & メインロジック
    // ==========================================
    async function handleSearch() {
        const keyword = keywordInput.value.trim();
        const userId = userIdInput.value;

        if (!keyword) {
            alert("料理名やキーワードを入力してください（例：オムライス、カレー）");
            return;
        }

        showLoading(keyword);

        try {
            // 🔥 【超重要】ブラウザで動いたURLをベースに、完全に固定の絶対パスで組み立てる！
            const url = `http://localhost/team-system2/server/page/get_recipe_ingredients.php?user_id=${userId}&keyword=${encodeURIComponent(keyword)}`;

            console.log("🚀 実際にフェッチするURLはこれです:", url); // デバッグ用

            const response = await fetch(url, { method: "GET" });

            // （以下、既存のコードと同じ）
            const rawText = await response.text();
            const data = parseJSON(rawText);
            if (!data) return;

            if (data.success === false || data.error) {
                showError(data.message || data.error || "データ取得に失敗しました。");
                return;
            }

            renderRecipeIngredients(data.ingredients, data.all_stocks, keyword);

        } catch (err) {
            showError(`通信エラーが発生しました: ${err.message}`);
        }
    }

    // ==========================================
    // 4. ヘルパー関数
    // ==========================================

    /**
     * 安全なJSONパース
     */
    function parseJSON(text) {
        try {
            return JSON.parse(text);
        } catch (e) {
            resultDiv.innerHTML = `
        <span class="scan-error-text">❌ システムエラー（JSONパース失敗）</span><br>
        <pre class="scan-error-debug">${text}</pre>
      `;
            return null;
        }
    }

    // ==========================================
    // 5. UIのレンダリング（HTML生成）
    // ==========================================

    function showLoading(keyword) {
        resultDiv.innerHTML = `
      <div class="scan-loading-box">
          <b class="scan-loading-title">🍳 AIが「${keyword}」の必要食材を分析中...</b><br>
          <span class="scan-loading-sub">冷蔵庫・冷凍庫の在庫と一致するか調べています。</span>
      </div>
    `;
    }

    function showError(message) {
        resultDiv.innerHTML = `<span class="scan-error-text">❌ ${message}</span>`;
    }

    /**
     * AIが判定した食材リストと在庫状況を表示
     */
    function renderRecipeIngredients(ingredients, allStocks, keyword) {
        let html = `<h3>💡 「${keyword}」に必要な主要食材（AI提案）</h3>`;
        html += "<p class='scan-loading-sub' style='margin-bottom: 15px;'>※ あなたの現在の在庫と自動でマッチングを行いました。</p>";

        html += "<table id='recipe-ingredients-table' style='width:100%; border-collapse: collapse;'>";
        html += "<tr>";
        html += "<th>必要食材</th>";
        html += "<th style='text-align: center;'>在庫状況</th>";
        html += "<th>保管場所</th>";
        html += "</tr>";

        ingredients.forEach(item => {
            // 在庫あり＝チェック、なし＝未チェック
            const checkedAttr = item.in_stock ? "checked" : "";
            // 在庫がある場合は行のスタイルを変更できるようにクラス付与
            const rowClass = item.in_stock ? "ingredient-row in-stock" : "ingredient-row out-of-stock";

            // 保管場所の日本語化表示
            let locationText = "<span style='color: #999;'>---</span>";
            if (item.in_stock) {
                locationText = item.location === 'freezer' ? "🥶 冷凍庫" : "❄️ 冷蔵庫";
            }

            html += `
        <tr class="${rowClass}" style="border-bottom: 1px solid #ddd; height: 45px;">
          <td style="font-weight: bold; padding-left: 10px;">
            ${item.food}
          </td>
          <td style="text-align: center;">
            <input type="checkbox" class="ingredient-match-check" ${checkedAttr} disabled style="transform: scale(1.2);">
            <span style="margin-left: 5px; font-weight: bold; color: ${item.in_stock ? '#28a745' : '#dc3545'};">
              ${item.in_stock ? 'あり' : 'なし'}
            </span>
          </td>
          <td>
            ${locationText}
          </td>
        </tr>
      `;
        });

        html += "</table>";

        // 💡 応用：全在庫データ（allStocks）を使い回してモーダル等を表示するためのボタン
        html += `
      <div style="margin-top: 20px; text-align: right;">
        <button class="bulk-save-button" id="openStockModalBtn" style="background-color: #17a2b8;">
          📦 現在の全在庫リストを確認 (${allStocks.length}件)
        </button>
      </div>
    `;

        resultDiv.innerHTML = html;

        // 全在庫確認用のイベントリスナー結合（もし必要であればモーダル展開処理をここへ）
        document.getElementById("openStockModalBtn").addEventListener("click", function () {
            console.log("現在の全在庫データ:", allStocks);
            alert(`現在、合計 ${allStocks.length} 件の食材がデータベースに登録されています。`);
        });
    }

})();