// ===================================================
// js/food_scan.js （クリーン＆メンテナンス重視版）
// ===================================================

(function () {
  "use strict";

  // ==========================================
  // 1. 設定・判定ルールの集約（ここを変更するだけで調整可能）
  // ==========================================
  const CONFIG = {
    API_ENDPOINT: "/team-system2/server/page/food_scan_server.php",
    DEFAULT_SHELF_LIFE: 3,
    DEFAULT_STORAGE: "冷蔵庫",
    DEFAULT_MEAT_WEIGHT: 250,
  };

  const MATCH_PATTERNS = {
    // 野菜室に振り分けるキーワード
    VEGETABLES: /(キャベツ|玉ねぎ|たまねぎ|大根|レタス|野菜|トマト|人参|きゅうり|ピーマン|白菜)/,
    // 日用品・食品以外（初期状態でチェックを外す、常温にする）
    NON_FOOD: /(ペーパー|ティッシュ|洗剤|シャンプー|ソープ|ゴミ袋|電池|サプリ)/i,
    // 重量(g)で管理したいもの
    WEIGHT_BASED: /(豚肉|牛肉|鶏肉|肉|鮭|サケ|サバ|刺身|魚)/,
    // 単位を個別に割り当てるルール
    UNITS: [
      { pattern: /(キャベツ|レタス|白菜)/, unit: "玉" },
      { pattern: /(大根|人参|にんじん|きゅうり|ネギ|ねぎ|ごぼう|アスパラ|バナナ|牛乳|飲料|水|麦茶)/, unit: "本" },
      { pattern: /(納豆|豆腐|きのこ|キノコ|しめじ|えのき|まいたけ|ヨーグルト)/, unit: "パック" }
    ]
  };

  // ==========================================
  // 2. DOM要素の初期化と検証
  // ==========================================
  const fileInput = document.getElementById("receipt-file");
  const resultDiv = document.getElementById("result");
  const fileNameSpan = document.getElementById("file-name");

  if (!fileInput || !resultDiv) return;

  fileInput.addEventListener("change", handleFileChange);

  // ==========================================
  // 3. イベントハンドラー & メインロジック
  // ==========================================
  function handleFileChange(e) {
    const file = e.target.files[0];
    if (!file) return;

    if (fileNameSpan) {
      fileNameSpan.innerText = file.name;
    }

    const reader = new FileReader();
    reader.onload = function (event) {
      sendToAI(event.target.result);
    };
    reader.readAsDataURL(file);
  }

  /**
   * Gemini AIに画像を送信してスキャンを実行
   */
  async function sendToAI(base64Image) {
    showLoading();

    try {
      const response = await fetch(CONFIG.API_ENDPOINT, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action: "scan", image: base64Image }),
      });

      const rawText = await response.text();
      console.log("--- サーバーからの応答データ ---", rawText);

      const data = parseJSON(rawText);
      if (!data) return; // エラー表示はparseJSON内で行う

      if (data.error) {
        showError(`エラー: ${data.error}`);
        return;
      }

      if (!data.items || !Array.isArray(data.items)) {
        showError("データ構造エラー: items配列が見つかりません。");
        return;
      }

      renderScanResultTable(data.items);

    } catch (err) {
      showError(`通信エラー: ${err.message}`);
    }
  }

  /**
   * 画面で選択された食材をDBへ保存
   */
  async function saveToStorage() {
    const rows = document.querySelectorAll("#scan-table .food-item-row");
    const items = [];

    rows.forEach(row => {
      const isChecked = row.querySelector(".food-select-checkbox").checked;
      if (!isChecked) return;

      items.push({
        food_name: row.querySelector(".food-name-input").value,
        quantity: parseFloat(row.querySelector(".food-quantity-input").value),
        unit: row.querySelector(".food-unit-text").innerText,
        storage_place: row.querySelector(".food-select-box").value,
        custom_expiry_date: row.querySelector(".food-date-input").value,
        term_type: row.querySelector(".food-term-select").value
      });
    });

    if (items.length === 0) {
      alert("登録対象の食材が1つも選択されていません。");
      return;
    }

    resultDiv.innerHTML = "<b class='scan-saving-box'>⏳ データベースに保存しています...</b>";

    try {
      const response = await fetch(CONFIG.API_ENDPOINT, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action: "save", items: items }),
      });

      const resData = await response.json();

      if (resData.success) {
        resultDiv.innerHTML = `<div class="scan-success-box">🎉 各保存場所へ、選択した食材の登録が完了しました！</div>`;
      } else {
        showError(`保存エラー: ${resData.error}`);
      }
    } catch (err) {
      showError("保存通信中にエラーが発生しました。");
    }
  }

  // ==========================================
  // 4. データ処理・ヘルパー関数（ロジックの分離）
  // ==========================================

  /**
   * 食品名から初期設定（数量、単位、保管場所、除外フラグ）を推測する
   */
  function predictFoodMeta(item) {
    const name = item.food_name || "";

    // 日用品かどうか
    const isNonFood = MATCH_PATTERNS.NON_FOOD.test(name);

    // 保存場所の判定
    let storage = CONFIG.DEFAULT_STORAGE;
    if (MATCH_PATTERNS.VEGETABLES.test(name)) {
      storage = "野菜室";
    } else if (isNonFood) {
      storage = "常温・パントリー";
    }

    // 数量と単位の判定
    let quantity = 1;
    let unit = "個";

    if (MATCH_PATTERNS.WEIGHT_BASED.test(name)) {
      quantity = item.estimated_weight && item.estimated_weight > 0 ? item.estimated_weight : CONFIG.DEFAULT_MEAT_WEIGHT;
      unit = "g";
    } else {
      quantity = item.quantity || 1;
      // 単位パターンの走査
      const found = MATCH_PATTERNS.UNITS.find(u => u.pattern.test(name));
      if (found) {
        unit = found.unit;
      }
    }

    // 日付の計算
    const shelfLifeDays = item.shelf_life_days !== undefined ? parseInt(item.shelf_life_days, 10) : CONFIG.DEFAULT_SHELF_LIFE;
    const expiryDate = getCalculatedDateStr(shelfLifeDays);

    return {
      isNonFood,
      storage,
      quantity,
      unit,
      expiryDate,
      isUseBy: item.term_type === 'use_by'
    };
  }

  /**
   * 日数から YYYY-MM-DD 形式の文字列を生成
   */
  function getCalculatedDateStr(days) {
    const date = new Date();
    date.setDate(date.getDate() + days);
    const yyyy = date.getFullYear();
    const mm = String(date.getMonth() + 1).padStart(2, '0');
    const dd = String(date.getDate()).padStart(2, '0');
    return `${yyyy}-${mm}-${dd}`;
  }

  /**
   * JSONパース（安全策・エラーハンドリング共通化）
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
  // 5. UIのレンダリング（HTML生成の集約）
  // ==========================================

  function showLoading() {
    resultDiv.innerHTML = `
      <div class="scan-loading-box">
          <b class="scan-loading-title">🤖 Gemini AIが解析中...</b><br>
          <span class="scan-loading-sub">数量や期限、保存場所を推測しています。最大1分ほどかかります。</span>
      </div>
    `;
  }

  function showError(message) {
    resultDiv.innerHTML = `<span class="scan-error-text">❌ ${message}</span>`;
  }

  /**
   * テーブル全体をレンダリング
   */
  function renderScanResultTable(items) {
    let html = "<h3>🎉 解析成功（登録したい食材のみチェックを残してください）</h3>";
    html += "<table id='scan-table'>";
    html += "<tr>";
    html += "<th class='col-check'>登録</th>";
    html += "<th>食品名</th>";
    html += "<th class='col-quantity'>数量</th>";
    html += "<th class='col-storage'>保存場所</th>";
    html += "<th class='col-term'>期限の種類</th>";
    html += "<th class='col-expiry'>期限日(編集可)</th></tr>";

    items.forEach(item => {
      html += createRowHtml(item);
    });

    html += "</table>";
    html += "<p class='save-action-area'><button class='bulk-save-button' id='bulkSaveBtn'>選択した食材をまとめて保存</button></p>";

    resultDiv.innerHTML = html;

    // イベントリスナーの再結合
    document.getElementById("bulkSaveBtn").addEventListener("click", saveToStorage);
  }

  /**
   * テーブルの1行分（<tr>）のHTMLを生成
   */
  function createRowHtml(item) {
    const meta = predictFoodMeta(item);
    const checkedAttr = meta.isNonFood ? "" : "checked";
    const rowClass = meta.isNonFood ? "food-item-row non-food" : "food-item-row";

    return `
      <tr class="${rowClass}">
          <td class="col-check">
              <input type="checkbox" class="food-select-checkbox" ${checkedAttr}>
          </td>
          <td>
              <input type="text" class="food-name-input" value="${item.food_name || ""}">
          </td>
          <td class="col-quantity" style="white-space: nowrap;">
              <input type="number" class="food-quantity-input" value="${meta.quantity}" step="0.1"> 
              <span class="food-unit-text">${meta.unit}</span>
          </td>
          <td>
              <select class="food-select-box">
                  <option value="冷蔵庫" ${meta.storage === '冷蔵庫' ? 'selected' : ''}>❄️ 冷蔵庫</option>
                  <option value="冷凍庫" ${meta.storage === '冷凍庫' ? 'selected' : ''}>🥶 冷凍庫</option>
                  <option value="常温・パントリー" ${meta.storage === '常温・パントリー' ? 'selected' : ''}>📦 常温・パントリー</option>
                  <option value="野菜室" ${meta.storage === '野菜室' ? 'selected' : ''}>🥬 野菜室</option>
              </select>
          </td>
          <td>
              <select class="food-term-select">
                  <option value="best_before" ${!meta.isUseBy ? 'selected' : ''} class="best-before">賞味期限</option>
                  <option value="use_by" ${meta.isUseBy ? 'selected' : ''} class="use-by">消費期限</option>
              </select>
          </td>
          <td>
              <input type="date" class="food-date-input" value="${meta.expiryDate}">
          </td>
      </tr>
    `;
  }

})();