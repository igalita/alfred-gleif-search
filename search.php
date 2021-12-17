<?php

require __DIR__ . '/vendor/autoload.php';

$term = $argv[1];

if (strlen($term) < 3) {
    return;
}

$search = new \Igalita\Gleif\Search;

echo $search->get($term);
