<?php

use V\Redsoft\FilesCleaner\IBlockFilesCleaner;

require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");
CModule::IncludeModule("iblock");

$FilesCleaner = new IBlockFilesCleaner();
$arRemovedFiles = $FilesCleaner->RunProcess();