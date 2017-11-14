<?php

use V\Redsoft\Offers\SKU;


require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");
CModule::IncludeModule("iblock");
CModule::IncludeModule("catalog");


global $USER;
$SKU = new SKU($USER->GetID(), 2, 3);

$arFieldsElement = [
    "NAME" => "16",
    "CODE" => "16".time(),
    "IBLOCK_SECTION_ID" => 8,
    "PREVIEW_PICTURE" => "/upload/catalog/e2b/e2b184fea5b3f5fe6fbcee110ae4ed19.jpg",
    "DETAIL_PICTURE" => "/upload/catalog/e2b/e2b184fea5b3f5fe6fbcee110ae4ed19.jpg",
];

$arPropsElement = [];

$productCatalogID = $SKU->AddProduct(
    $arFieldsElement,
    $arPropsElement
);

if ($productCatalogID["ID"]) {
    $arFieldsOffer = [
        "AVAILABLE" => "Y",
        "VAT_ID" => "2",
        "VAT_INCLUDED" => "Y",
        "PURCHASING_PRICE" => "200",
        "PURCHASING_CURRENCY" => "USD",
    ];


    $arFieldsElement["CODE"] = $arFieldsElement["CODE"].time();
    $arPropsOffer = [
        $SKU::$PROPERTY_BINDING_ID => $productCatalogID["ID"]
    ];

    $offer = $SKU->AddOffer(
        $arFieldsElement,
        $arPropsOffer,
        $arFieldsOffer
    );
}