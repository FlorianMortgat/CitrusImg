<?php

// https://gist.github.com/liunian/9338301  < MrCaspan >
function humanStorageSize($bytes) {
    $i = floor(log($bytes, 1024));
    return frnum(round($bytes / 1024 ** $i, [0,0,2,2,3][$i]) . ' ' . ['o','Ko','Mo','Go','To'][$i]);
}

function frnum($num) {
    return str_replace('.', ',', $num);
}

// function getDB() {
//     static $db = null;
//     $isDbInitialized = file_exists(DB_PATH);
//     if ($db === null) {
//         $db = new PDO('sqlite:' . DB_PATH);
//     }
//     if (!$isDbInitialized) {

//     }
// }