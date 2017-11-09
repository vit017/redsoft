<?php


spl_autoload_register("autoloaderRedsoft");

function autoloaderRedsoft($className) {
    include_once str_replace('\\', '/', $_SERVER["DOCUMENT_ROOT"] . "/". $className . '.php');
}