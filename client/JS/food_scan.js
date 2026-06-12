// ===================================================
// js/food_scan.js （画像選択で自動解析スタート・超快適版）
// ===================================================

(function () {
  "use strict";

  console.log("【デバッグ】food_scan.js（自動解析版）が正常にロードされました。");

  var fileInput = document.getElementById("receipt-file");
  var resultDiv = document.getElementById("result");
  var fileNameSpan = document.getElementById("file-name");

  // 要素が不足している場合は初期化処理をスキップ
  if (!fileInput || !resultDiv) {
    console.log("【デバッグ】必要なDOM要素が存在しないため、処理を中断します。");
    return;
  }

  // 🔥 変更点：ファイルが「選択された瞬間」に即時実行するイベント
  fileInput.addEventListener("change", function (e) {
    var file = e.target.files[0];
    if (!file) return;

    if (fileNameSpan) {
      fileNameSpan.innerText = file.name;
    }

    var reader = new FileReader();
    reader.onload = function (event) {
      var base64Image = event.target.result;

      // 💡 ボタンを押させる代わりに、ここで自動的にAI送信関数を呼び出す
      sendToAI(base64Image);
    };
    reader.readAsDataURL(file);
  });

  // 今日からN日後の日付を YYYY-MM-DD で返すヘルパー関数
  function getCalculatedDateStr(days) {
    var date = new Date();
    date.setDate(date.getDate() + days);
    var yyyy = date.getFullYear();
    var mm = String(date.getMonth() + 1).padStart(2, '0');
    var dd = String(date.getDate()).padStart(2, '0');
    return yyyy + "-" + mm + "-" + dd;
  }

  // AI通信ロジック
  async function sendToAI(base64Image) {
    resultDiv.innerHTML = `
      <div style="padding: 10px; background: #e8f4fd; border-left: 4px solid #3498db;">
          <b style="color: #2980b9;">🤖 Gemini AIが解析中...</b><br>
          <span style="font-size: 0.9em; color: #555;">重量計算、名寄せ、期限を予測しています。最大1分ほどかかります。</span>
      </div>
    `;

    try {
      var response = await fetch(
        "/team-system2/server/page/food_scan_server.php",
        {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify({
            action: "scan",
            image: base64Image,
          }),
        }
      );

      var rawText = await response.text();
      console.log("--- サーバーからの応答データ ---", rawText);

      var data;
      try {
        data = JSON.parse(rawText);
      } catch (e) {
        resultDiv.innerHTML = `
          <span style="color:red; font-weight:bold;">❌ システムエラー（JSONパース失敗）</span><br>
          <p>サーバーからの応答が正しいJSON形式ではありません。以下に生データを出力します：</p>
          <pre style="background:#eee; padding:10px; border-radius:4px; overflow-x:auto;">${rawText}</pre>
        `;
        return;
      }

      if (data.error) {
        resultDiv.innerHTML = `<span style="color:red; font-weight:bold;">❌ エラー: ${data.error}</span>`;
        return;
      }

      if (!data.items || !Array.isArray(data.items)) {
        resultDiv.innerHTML = `<span style="color:red; font-weight:bold;">❌ データ構造エラー: items配列が見つかりません。</span>`;
        return;
      }

      // 解析結果テーブル構築
      var html = "<h3>🎉 解析成功（登録したい食材のみチェックを残してください）</h3>";
      html += "<p style='font-size:0.9em; color:#e67e22; margin-bottom:10px;'>※日用品など不要なものは左のチェックを外せば、保存されずに除外できます。</p>";
      html += "<table id='scan-table' style='width:100%; border-collapse: collapse;'>";
      html += "<tr style='background:#eee; text-align:left;'>";
      html += "<th style='padding:8px; border:1px solid #ddd; text-align:center; width:50px;'>登録</th>";
      html += "<th style='padding:8px; border:1px solid #ddd;'>食品名</th>";
      html += "<th style='padding:8px; border:1px solid #ddd; width:100px;'>予想重量</th>";
      html += "<th style='padding:8px; border:1px solid #ddd; width:140px;'>保存場所</th>";
      html += "<th style='padding:8px; border:1px solid #ddd; width:120px;'>期限の種類</th>";
      html += "<th style='padding:8px; border:1px solid #ddd; width:150px;'>期限日(編集可)</th></tr>";

      data.items.forEach(function (item) {
        var days = item.shelf_life_days !== undefined ? parseInt(item.shelf_life_days, 10) : 3;
        var defaultDateStr = getCalculatedDateStr(days);
        var isUseBy = item.term_type === 'use_by';

        var defaultLocation = "冷蔵庫";
        if (item.food_name.match(/(キャベツ|玉ねぎ|たまねぎ|大根|レタス|野菜|トマト|人参|きゅうり|ピーマン|白菜)/)) {
          defaultLocation = "野菜室";
        }

        var isNonFood = item.food_name.match(/(ペーパー|ティッシュ|洗剤|シャンプー|ソープ|ゴミ袋|電池|サプリ)/i);
        var checkedAttr = isNonFood ? "" : "checked";

        html += `<tr class="food-item" style="border-bottom:1px solid #ddd; ${isNonFood ? 'background:#f9f9f9; opacity:0.65;' : ''}">
                      <td style="padding:8px; border:1px solid #ddd; text-align:center;">
                          <input type="checkbox" class="food-select-checkbox" ${checkedAttr} style="transform: scale(1.3); cursor:pointer;">
                      </td>
                      <td style="padding:8px; border:1px solid #ddd;"><input type="text" class="food-name" value="${item.food_name || ""}" style="width:90%; padding:4px;"></td>
                      <td style="padding:8px; border:1px solid #ddd;"><input type="number" class="food-weight" value="${item.estimated_weight || 0}" style="width:70px; padding:4px;"> </td>
                      
                      <td style="padding:8px; border:1px solid #ddd;">
                          <select class="food-storage-place" style="padding: 4px; border-radius: 4px; width:100%;">
                              <option value="冷蔵庫" ${defaultLocation === '冷蔵庫' ? 'selected' : ''}>❄️ 冷蔵庫</option>
                              <option value="冷凍庫">🥶 冷凍庫</option>
                              <option value="常温・パントリー" ${isNonFood ? 'selected' : ''}>📦 常温・パントリー</option>
                              <option value="野菜室" ${defaultLocation === '野菜室' ? 'selected' : ''}>🥬 野菜室</option>
                          </select>
                      </td>

                      <td style="padding:8px; border:1px solid #ddd;">
                          <select class="food-term-type" style="padding: 4px; border-radius: 4px; font-weight: bold; width:100%;">
                              <option value="best_before" ${!isUseBy ? 'selected' : ''} style="color:#2ece7d;">賞味期限</option>
                              <option value="use_by" ${isUseBy ? 'selected' : ''} style="color:#ff4d4d;">消費期限</option>
                          </select>
                      </td>
                      <td style="padding:8px; border:1px solid #ddd;">
                          <input type="date" class="food-expiry-date" value="${defaultDateStr}" style="padding: 4px; border-radius: 4px; width:90%;">
                      </td>
                  </tr>`;
      });
      html += "</table>";
      html += "<p style='margin-top:15px;'><button class='save-btn' id='bulkSaveBtn' style='background:#2ece7d; color:white; padding:10px 20px; border:none; border-radius:5px; font-weight:bold; cursor:pointer; width:100%; font-size:1.1em;'>選択した食材をまとめて保存</button></p>";

      resultDiv.innerHTML = html;

      // 保存処理イベントの紐付け
      document.getElementById("bulkSaveBtn").addEventListener("click", saveToStorage);

    } catch (err) {
      resultDiv.innerHTML = `<span style="color:red; font-weight:bold;">❌ 通信エラー</span><br>${err.message}`;
      console.error(err);
    }
  }

  // データベース一括保存
  async function saveToStorage() {
    var rows = document.querySelectorAll("#scan-table .food-item");
    var items = [];

    rows.forEach(function (row) {
      var isChecked = row.querySelector(".food-select-checkbox").checked;
      if (isChecked) {
        items.push({
          food_name: row.querySelector(".food-name").value,
          estimated_weight: parseInt(row.querySelector(".food-weight").value, 10),
          storage_place: row.querySelector(".food-storage-place").value,
          custom_expiry_date: row.querySelector(".food-expiry-date").value,
          term_type: row.querySelector(".food-term-type").value
        });
      }
    });

    if (items.length === 0) {
      alert("登録対象の食材が1つも選択されていません。");
      return;
    }

    resultDiv.innerHTML = "<b style='color: #e67e22;'>⏳ データベースに保存しています...</b>";

    try {
      var response = await fetch(
        "/team-system2/server/page/food_scan_server.php",
        {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify({
            action: "save",
            items: items,
          }),
        }
      );
      var resData = await response.json();

      if (resData.success) {
        resultDiv.innerHTML = `<div style="padding:15px; background:#eef9f1; border-left:4px solid #2ece7d; color:green; font-weight:bold;">🎉 各保存場所（冷蔵庫・常温など）へ、選択した食材の登録が完了しました！</div>`;
      } else {
        resultDiv.innerHTML = `<span style="color:red; font-weight:bold;">❌ 保存エラー: ${resData.error}</span>`;
      }
    } catch (err) {
      resultDiv.innerHTML = `<span style="color:red; font-weight:bold;">❌ 保存通信中にエラーが発生しました。</span>`;
      console.error(err);
    }
  }
})();