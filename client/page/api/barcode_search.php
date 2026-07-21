<?php

ini_set('display_errors', 0);
error_reporting(E_ALL);

header("Content-Type: application/json; charset=UTF-8");


$data = json_decode(file_get_contents("php://input"), true);


if (!isset($data["barcode"])) {

    echo json_encode([
        "success" => false,
        "message" => "バーコードがありません"
    ]);

    exit;
}


$barcode = $data["barcode"];


// Open Food Facts API
$url = "https://world.openfoodfacts.org/api/v2/product/"
    . $barcode;



// curl通信
$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);


// ローカル開発用 SSL無効
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);



$response = curl_exec($ch);



if ($response === false) {

    echo json_encode([
        "success" => false,
        "message" => "商品API接続エラー：" . curl_error($ch)
    ]);

    curl_close($ch);
    exit;
}


curl_close($ch);



$json = json_decode($response, true);



if (
    isset($json["status"]) &&
    $json["status"] == 1
) {


    $product = $json["product"];


echo json_encode([

    "success" => true,

    "barcode" => $barcode,

    "name" => $product["product_name"] ?? "名称不明",

    "brand" => $product["brands"] ?? "",

    "brands_tags" => $product["brands_tags"][0] ?? "",

    "image" => $product["image_url"] ?? "",

    "category" => $product["categories"] ?? "",

    "quantity" => $product["quantity"] ?? ""

]);

} else {


    echo json_encode([

        "success" => false,

        "barcode" => $barcode,

        "message" => "商品情報が見つかりませんでした"

    ]);
}
