<?php

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

$config = [
    'id' => 'loan-api-test',
    'basePath' => dirname(__DIR__) . '/src',
    'bootstrap' => ['log'],
    'components' => [
        'db' => require __DIR__ . '/../src/config/db.php',
        'request' => [
            'class' => 'yii\web\Request',
            'scriptUrl' => '/index.php',
            'hostInfo' => 'http://localhost:8380',
            'cookieValidationKey' => 'test-secret-key-12345',
            'enableCsrfValidation' => false,
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                'POST requests' => 'loan/requests',
                'GET processor' => 'loan/processor',
            ],
        ],
        'log' => [
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning', 'info'],
                ],
            ],
        ],
    ],
];

new yii\web\Application($config);