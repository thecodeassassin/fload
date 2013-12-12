<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

require dirname(__FILE__) . '/../vendor/autoload.php';

$configFile = dirname(__FILE__) . '/../app/config/config.php';
if (!is_readable($configFile)) {
    die(sprintf('Missing config file! (%s)' . PHP_EOL, $configFile));
}

$config = require $configFile;

if (!is_array($config) || !array_key_exists('app', $config)) {
    die('Config file not returning an array or missing application config.'.PHP_EOL);
}

/**
 * The main bootstrapper for Fload (initializes the framework)
 */
$app = new \Slim\Slim(
    array_merge(
        $config['app'],
        array(
            'log.writer' => new \Fload\Log\Writer($config['log'])
        )
    )
);
$app->appConfig = $config;

$container = new Pimple();
$app->di = $container;
/**
 * Add the fload middleware's
 */
$responseObject = new \Fload\Response\Handler($app);

try {

    // the dataprovider
    $app->add(new \Fload\DataProvider());


    // middleware for handling the requests
    $app->add(new \Fload\Requests());

    // register the error handler
    $app->error(
        function (\Exception $e) use ($responseObject) {
            $responseObject->handleException($e);
        }
    );

    $app->run();

} catch (\Exception $e) {
    $responseObject->handleException($e);
}
