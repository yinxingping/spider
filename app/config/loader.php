<?php

$loader = new \Phalcon\Loader();

include(BASE_PATH . '/vendor/autoload.php');

$loader->registerNamespaces(
    [
        'Spider\Library' => $config->application->libraryDir,
        'Spider\Parser' => $config->application->parsersDir,
    ]
);
$loader->registerDirs([
    APP_PATH . '/tasks',
    APP_PATH . '/models'
]);
$loader->register();
