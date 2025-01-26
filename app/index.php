<?php
declare(strict_types=1);

require 'vendor/autoload.php';

include 'Router.php';

try {
    $api = new Router();
    echo $api->run();
} catch (Exception $e) {
    echo json_encode(Array('error' => $e->getMessage()));
}