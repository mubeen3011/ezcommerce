<?php
return [
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=localhost;dbname=ezcomm',
            //'dsn' => 'mysql:host=usboxingdemoserver.csbyf4ygnnle.ap-southeast-1.rds.amazonaws.com;dbname=ezcomm-usboxing-live',
            'username' => 'root',
            //'username' => 'admin',
            'password' => '',
            //'password' => 'usboxing_demo',
            'charset' => 'utf8',
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            'viewPath' => '@common/mail',
            'transport' => [
                'class' => 'Swift_SmtpTransport',
                'host' => 'smtp.axleolio.com',
                'username' => 'alerts.tool@axleolio.com',
                'password' => '[X2wnuJ(!%]h', // your password
                'port' => '587',
//                'encryption' => 'tls',
            ],
        ],
    ],
];
