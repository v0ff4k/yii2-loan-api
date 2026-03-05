<?php

$db = require __DIR__ . '/db.php';

return [
    'id' => 'loan-api-console',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'app\controllers',

    // Явно подключаем модуль миграций
    'modules' => [
        'migrate' => [
            'class' => 'yii\console\controllers\MigrateController',
            'migrationPath' => '@app/console/migrations',
            'migrationTable' => '{{%migration}}',
        ],
    ],

    'components' => [
        'db' => $db,
        'log' => [
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
    ],
    'params' => require __DIR__ . '/params.php',
];