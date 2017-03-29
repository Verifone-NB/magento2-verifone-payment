<?php

function encodeFunc($value) {
    return "\"$value\"";
}

$source = [];

$handle = fopen('source.csv', "r");
while (($data = fgetcsv($handle, 0, ',')) !== false) {
    $source[md5($data[0])] = $data[1];
}

$new = [];
$handleA = fopen('fi_FI.csv', "r");
while (($data = fgetcsv($handleA, 0, ',')) !== false) {
    var_dump($data);
    if (isset($source[md5($data[0])])) {
        $new[] = [$data[0], $source[md5($data[0])]];
    } else {
        $new[] = [$data[0], $data[1]];
    }
}

$file = fopen("new_fi_FI.csv", "w");

foreach ($new as $line) {
    fputcsv($file, array_map('encodeFunc', $line), ',');
}

fclose($file);