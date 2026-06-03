<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>レシート食材重量逆算スキャナー</title>
    <style>
        body {
            font-family: sans-serif;
            margin: 20px;
            background-color: #f4f7f6;
            color: #333;
        }

        .upload-container {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            margin-bottom: 20px;
            text-align: center;
        }

        /* ファイル選択ボタンを押しやすく装飾 */
        .file-label {
            display: inline-block;
            background-color: #007bff;
            color: white;
            padding: 12px 24px;
            font-size: 16px;
            font-weight: bold;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .file-label:hover {
            background-color: #0056b3;
        }

        #receipt-file {
            display: none;
            /* 本物のボタンは隠す */
        }

        #result {
            padding: 15px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            white-space: pre-wrap;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #e9ecef;
        }

        input[type="number"] {
            width: 80px;
            padding: 4px;
        }

        input[type="text"] {
            width: 150px;
            padding: 4px;
        }

        .save-btn {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
        }

        .save-btn:hover {
            background-color: #218838;
        }
    </style>
</head>

<body>

    <h1>レシート食材重量逆算スキャナー</h1>

    <div class="upload-container">
        <p>レシートの写真を撮影するか、画像を選択してください</p>
        <label class="file-label">
            📸 写真を撮る / 画像を選択
            <input type="file" id="receipt-file" accept="image/*" capture="environment">
        </label>
    </div>

    <div id="result">ここに解析結果が表示されます</div>

    <script>
        const fileInput = document.getElementById('receipt-file');
        const resultDiv = document.getElementById('result');

        // ファイルが選択（または撮影）されたら自動で解析スタート
        fileInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (!file) return;

            resultDiv.innerText = "画像を読み込み中...";

            const reader = new FileReader();
            reader.onload = function(event) {
                const base64Image = event.target.result;
                sendToAI(base64Image);
            };
            reader.readAsDataURL(file);
        });

        // Gemini API サーバーへ画像を送信
        async function sendToAI(base64Image) {
            resultDiv.innerText = "Gemini APIで解析中（グラム数逆算中）...\n※AIの思考と通信に最大1分ほどかかる場合があります。そのままお待ちください。";

            try {
                const response = await fetch('/team-system2/server/page/food_scan_server.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'scan',
                        image: base64Image
                    })
                });

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
                let html = "<h3>解析成功（冷蔵庫に登録する食材）</h3><table id='scan-table'>";
                html += "<tr><th>食品名</th><th>金額</th><th>予想重量(g)</th></tr>";

                data.items.forEach((item) => {
                    html += `<tr class="food-item">
                        <td><input type="text" class="food-name" value="${item.food_name || ''}"></td>
                        <td class="food-price" data-price="${item.price || 0}">${item.price || 0}円</td>
                        <td><input type="number" class="food-weight" value="${item.estimated_weight || 0}"> g</td>
                    </tr>`;
                });
                html += "</table><p><button class='save-btn' onclick='saveToStorage()'>この内容で冷蔵庫に保存</button></p>";
                resultDiv.innerHTML = html;

            } catch (err) {
                resultDiv.innerText = "通信中に予期せぬエラーが発生しました。";
                console.error(err);
            }
        }

        // 修正されたデータをDBに一括保存
        async function saveToStorage() {
            const rows = document.querySelectorAll('#scan-table .food-item');
            const items = [];

            rows.forEach(row => {
                items.push({
                    food_name: row.querySelector('.food-name').value,
                    price: parseInt(row.querySelector('.food-price').getAttribute('data-price'), 10),
                    estimated_weight: parseInt(row.querySelector('.food-weight').value, 10)
                });
            });

            resultDiv.innerText = "データベースに登録中...";

            try {
                const response = await fetch('/team-system2/server/page/food_scan_server.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'save',
                        items: items
                    })
                });
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
    </script>
</body>

</html>