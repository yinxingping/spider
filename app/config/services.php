<?php

$di->setShared('config', function () {
    return include APP_PATH . '/config/config.php';
});

$di->setShared('db', function () {
    $config = $this->getConfig();

    $class = 'Phalcon\Db\Adapter\Pdo\\' . $config->database->adapter;

    $params = [
        'host'     => $config->database->host,
        'username' => $config->database->username,
        'password' => $config->database->password,
        'dbname'   => $config->database->dbname,
        'charset'  => $config->database->charset
    ];

    $connection = new $class($params);

    return $connection;
});

$di->setShared('logger', function () {
    return \Phalcon\Logger\Factory::load($this->getConfig()->logger);
});

$di->setShared('downloader', function () {
    return new \Spider\Library\Download($this->getLogger());
});

$di->setShared('oss', function () {
    $config = $this->getConfig();

    return new \OSS\OssClient(
        $config->oss->accessKeyId,
        $config->oss->accessKeySecret,
        $config->oss->endpoint
    );
});

/*
 * @todo 根据Task选择相应类别的处理器
 */
$di->setShared('handler', function () {
    return new \Spider\Parser\BookHandler();
});

