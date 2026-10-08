<?php
$magentoBootstrap = __DIR__ . '/../../../../dev/tests/unit/framework/bootstrap.php';
if (file_exists($magentoBootstrap)) {
    require_once $magentoBootstrap;
} else {
    require_once __DIR__ . '/../vendor/autoload.php';
}
