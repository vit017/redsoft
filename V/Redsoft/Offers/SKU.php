<?php


namespace V\Redsoft\Offers;

class SKU
{

    private $userID;
    private $catalogIBlockID;
    private $offersIBlockID;

    private $arMeasures = [];
    private $arCurrencies = [];

    public static $IS_AVAILABLE = "Y";
    public static $PROPERTY_BINDING_ID = 31;

    public static $OFFER_TYPES = [
        "SAMPLE" => 1,
        "COMPLECT" => 2,
        "WITH_OFFERS" => 3,
        "OFFER" => 4,
    ];

    public static $PAYMENTS = [
        "DISPOSABLE" => "S",
        "REGULAR" => "R",
        "TRIAL" => "T"
    ];


    public function __construct(int $userID, int $catalogID, int $offersID)
    {
        \CModule::IncludeModule("catalog");

        $this->userID = $userID;
        $this->catalogIBlockID = $catalogID;
        $this->offersIBlockID = $offersID;

        $this->DefineMeasures();
        $this->DefineCurrencies();

        $this->arDefaultFieldsOffer = [
            "AVAILABLE" => self::$IS_AVAILABLE,
            "PURCHASING_CURRENCY" => $this->GetDefaultCurrency(),
            "MEASURE" => $this->GetDefaultMeasure(),
            "PRICE_TYPE" => self::$PAYMENTS["DISPOSABLE"],
            "CAN_BUY_ZERO" => "Y",
            "QUANTITY" => 100
        ];
    }

    private function DefineMeasures()
    {
        $dbResultList = \CCatalogMeasure::GetList(["ID" => "ASC"], [], false, false, []);
        while ($arResult = $dbResultList->Fetch()) {
            $this->arMeasures[] = $arResult;
        }
    }

    private function DefineCurrencies()
    {
        $dbResultList = \CCurrency::GetList(($by = "NAME"), ($order = "ASC"));
        while ($arResult = $dbResultList->Fetch()) {
            $this->arCurrencies[] = $arResult;
        }
    }

    private function GetDefaultCurrency(): string {
        foreach ($this->arCurrencies as $arCurrency) {
            if ("Y" === $arCurrency["BASE"]) {
                return $arCurrency["CURRENCY"];
            }
        }

        return $this->arCurrencies[0]["CURRENCY"];
    }

    private function GetDefaultMeasure(): string {
        foreach ($this->arMeasures as $arMeasure) {
            if ("Y" === $arMeasure["IS_DEFAULT"]) {
                return $arMeasure["ID"];
            }
        }

        return $this->arMeasures[0]["ID"];
    }

    public function AddProduct(array $arFields, array $arProps, bool $isOffer = false): array
    {
        $el = new \CIBlockElement;

        $arLoadProduct = [
            "MODIFIED_BY" => $this->userID,
            "IBLOCK_ID" => $isOffer ? $this->offersIBlockID : $this->catalogIBlockID,
            "PROPERTY_VALUES" => $arProps,
            "PREVIEW_PICTURE" => \CFile::MakeFileArray($arFields["PREVIEW_PICTURE"]),
            "DETAIL_PICTURE" => \CFile::MakeFileArray($arFields["DETAIL_PICTURE"]),
        ];
        unset($arFields["PREVIEW_PICTURE"]);
        unset($arFields["DETAIL_PICTURE"]);

        $arLoadProduct = array_merge($arLoadProduct, $arFields);

        if ($productID = $el->Add($arLoadProduct)) {
            return ["RESULT" => true, "ID" => $productID];
        } else {
            return ["RESULT" => false, "MESSAGE" => $el->LAST_ERROR];
        }
    }


    public function AddOffer(array $arFieldsElement, array $arPropsElement, array $arFieldsOffer): array
    {
        $productOffer = $this->AddProduct($arFieldsElement, $arPropsElement, true);
        if (!$productOffer["ID"]) {
            return $productOffer;
        }

        $arFieldsOffer["ID"] = $productOffer["ID"];

        foreach ($this->arDefaultFieldsOffer as $key => $value) {
            if (!array_key_exists($key, $arFieldsOffer)) {
                $arFieldsOffer[$key] = $this->arDefaultFieldsOffer[$key];
            }
        }

        return ["RESULT" => \CCatalogProduct::Add($arFieldsOffer)];
    }



}