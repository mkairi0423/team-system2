const codeReader = new ZXing.BrowserMultiFormatReader();

document.getElementById("startScan").addEventListener("click", () => {
  codeReader.decodeFromVideoDevice(null, "video", (result, err) => {
    if (result) {
      const barcode = result.text;

      console.log("読み取り成功:", barcode);

      document.getElementById("barcodeResult").textContent =
        "JANコード：" + barcode;

      // hiddenへJANコード保存
      document.getElementById("barcode").value = barcode;

      fetch("../page/api/barcode_search.php", {
        method: "POST",

        headers: {
          "Content-Type": "application/json",
        },

        body: JSON.stringify({
          barcode: barcode,
        }),
      })
        .then((res) => {
          // PHPの返答確認
          return res.text();
        })

        .then((text) => {
          console.log("PHP返答:", text);

          let data;

          try {
            data = JSON.parse(text);
          } catch (e) {
            console.error("JSON変換失敗:", e);

            document.getElementById("productResult").innerHTML =
              "サーバーエラー<br>" + text;

            return;
          }

          console.log("API結果:", data);

          if (data.success) {
            // 商品情報を登録処理用に保存
            window.productData = data;

            document.getElementById("productResult").innerHTML = `

<h3>商品情報</h3>

<strong>商品名：</strong>${data.name}<br>

<strong>メーカー：</strong>${data.brand}<br>

<strong>ブランド：</strong>${data.brands_tags}<br>

<strong>内容量：</strong>${data.quantity}<br>

<strong>カテゴリ：</strong>${data.category}<br>


<br>

<img src="${data.image}" width="120">


<hr>


<h3>登録情報</h3>


<strong>登録日時：</strong>${new Date().toLocaleDateString()}<br>


<label>
保存場所：
<select id="storage">
    <option>冷蔵庫</option>
    <option>冷凍庫</option>
    <option>常温</option>
</select>
</label>


<br>


<label>
賞味期限：
<input type="date" id="expiration">
</label>




                        ${
                          data.image
                            ? `<img src="${data.image}" width="100">`
                            : ""
                        }

                    `;
          } else {
            document.getElementById("productResult").innerHTML =
              data.message ?? "商品情報が見つかりませんでした。";
          }
        })

        .catch((error) => {
          console.error("通信エラー:", error);

          document.getElementById("productResult").innerHTML =
            "通信エラーが発生しました";
        });

      codeReader.reset();
    }

    if (err && !(err instanceof ZXing.NotFoundException)) {
      console.error(err);
    }
  });
});

document.getElementById("stopScan").addEventListener("click", () => {
  codeReader.reset();
});
