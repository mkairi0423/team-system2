const fileInput = document.getElementById("receipt-file");
const resultDiv = document.getElementById("result");

// ファイルが選択（または撮影）されたら自動で解析スタート
fileInput.addEventListener("change", (e) => {
  const file = e.target.files[0];
  if (!file) return;

  resultDiv.innerText = "画像を読み込み中...";

  const reader = new FileReader();
  reader.onload = function (event) {
    const base64Image = event.target.result;
    sendToAI(base64Image);
  };
  reader.readAsDataURL(file);
});

// Gemini API サーバーへ画像を送信
async function sendToAI(base64Image) {
  resultDiv.innerText =
    "Gemini APIで解析中（グラム数逆算中）...\n※AIの思考と通信に最大1分ほどかかる場合があります。そのままお待ちください。";

  try {
    const response = await fetch(
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
      },
    );

    const rawText = await response.text();
    console.log("--- サーバーからの生応答データ ---");
    console.log(rawText);

    let data;
    try {
      data = JSON.parse(rawText);
    } catch (e) {
      resultDiv.innerHTML = `<span style="color:red; font-weight:bold;">システムエラー（JSONパース失敗）</span><br><br>${rawText}`;
      return;
    }

    if (data.error) {
      resultDiv.innerHTML = `<span style="color:red; font-weight:bold;">エラー: ${data.error}</span>`;
      return;
    }

    if (!data.items || !Array.isArray(data.items)) {
      resultDiv.innerText = "データ構造が正しくありません。";
      return;
    }

    // フォーム付きテーブルを描画
    let html =
      "<h3>解析成功（冷蔵庫に登録する食材）</h3><table id='scan-table'>";
    html += "<tr><th>食品名</th><th>金額</th><th>予想重量(g)</th></tr>";

    data.items.forEach((item) => {
      html += `<tr class="food-item">
                        <td><input type="text" class="food-name" value="${item.food_name || ""}"></td>
                        <td class="food-price" data-price="${item.price || 0}">${item.price || 0}円</td>
                        <td><input type="number" class="food-weight" value="${item.estimated_weight || 0}"> g</td>
                    </tr>`;
    });
    html +=
      "</table><p><button class='save-btn' onclick='saveToStorage()'>この内容で冷蔵庫に保存</button></p>";
    resultDiv.innerHTML = html;
  } catch (err) {
    resultDiv.innerText = "通信中に予期せぬエラーが発生しました。";
    console.error(err);
  }
}

// 修正されたデータをDBに一括保存
async function saveToStorage() {
  const rows = document.querySelectorAll("#scan-table .food-item");
  const items = [];

  rows.forEach((row) => {
    items.push({
      food_name: row.querySelector(".food-name").value,
      price: parseInt(
        row.querySelector(".food-price").getAttribute("data-price"),
        10,
      ),
      estimated_weight: parseInt(row.querySelector(".food-weight").value, 10),
    });
  });

  resultDiv.innerText = "データベースに登録中...";

  try {
    const response = await fetch(
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
      },
    );
    const resData = await response.json();

    if (resData.success) {
      resultDiv.innerHTML = `<h3 style="color:green;">🎉 冷蔵庫にすべての食材を登録しました！</h3>`;
    } else {
      resultDiv.innerHTML = `<span style="color:red; font-weight:bold;">保存エラー: ${resData.error}</span>`;
    }
  } catch (err) {
    resultDiv.innerText = "保存通信中にエラーが発生しました。";
    console.error(err);
  }
}
