<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

echo 'Cleaning tmp files ...' . PHP_EOL;

$tmpFiles = glob(__DIR__ . '/tmp/FIXED_*.jpg');

foreach ($tmpFiles as $file) {
    unlink($file);
}
