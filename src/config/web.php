<?php

// for optimize, getrid from vars !
$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

return [
    'id' => 'loan-api',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'components' => [
        'request' => [
            'parsers' => ['application/json' => 'yii\web\JsonParser'],
            'cookieValidationKey' => 'loan-api-secret-key-12345',
            'enableCsrfValidation' => false,  // ← Отключаем CSRF для API!
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                // Строгие правила с методом (Глагол + Путь = Роут)
                ['pattern' => 'requests', 'route' => 'loan/requests', 'verb' => 'POST'],
                ['pattern' => 'processor', 'route' => 'loan/processor', 'verb' => 'GET'],
            ],
        ],
        'db' => $db,
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                ['class' => 'yii\log\FileTarget', 'levels' => ['error', 'warning']],
            ],
        ],
    ],
    'params' => $params,
];