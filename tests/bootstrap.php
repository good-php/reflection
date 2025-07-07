<?php

error_reporting(E_ALL);

$loader = require __DIR__ . '/../vendor/autoload.php';

// require dirname(__DIR__) . '/vendor/hamcrest/hamcrest-php/hamcrest/Hamcrest.php';

Phake::setClient(Phake::CLIENT_PHPUNIT10);
