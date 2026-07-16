document.getElementById("registerBarcode").addEventListener("click", function () {

    const food = {
        jan: document.getElementById("barcode").value,

        name: window.productData.name,
        maker: window.productData.brand,
        brand: window.productData.brands_tags,
        quantity: window.productData.quantity,
        category: window.productData.category,
        image: window.productData.image,

        storage: document.getElementById("storage").value,
        expiration: document.getElementById("expiration").value
    };

    console.log(food);

    fetch("./api/register_food.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify(food)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert("DBへ登録しました");
        } else {
            alert("登録失敗\n" + data.message);
        }
    })
    .catch(err => {
        console.error(err);
        alert("通信エラーが発生しました。");
    });

});