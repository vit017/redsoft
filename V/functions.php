<?php

function dd2($data, $die = true)
{
    echo '<pre>';
    var_dump($data);
    echo '</pre>';

    if ($die) {
        die();
    }
}