<?php

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(paths: __DIR__ . '/../config');
$dotenv->load();

require_once __DIR__ . '/helpers.php';
