<?php
require dirname(__FILE__) . '/../vendor/autoload.php';

/**
 * The main bootstrapper for Fload (initializes the framework)
 */
$app = new \Slim\Slim();


$app->run();
