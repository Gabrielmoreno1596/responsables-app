<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use CECNSR\App;

$app = (new App())->get();
$app->run();
