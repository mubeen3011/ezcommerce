<?php
return [
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    //'timeZone' => 'Asia/Karachi',
    'timeZone' => 'Asia/Kuala_lumpur',
    //'timeZone' => 'America/Chicago',
    'components' => [
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        

    ],
];
