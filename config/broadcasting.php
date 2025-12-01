<?php

return [

    'default' => env('BROADCAST_DRIVER', 'null'),

    'connections' => [

//        'pusher' => [
//            'driver' => 'pusher',
//            'key' => env('PUSHER_APP_KEY'),
//            'secret' => env('PUSHER_APP_SECRET'),
//            'app_id' => env('PUSHER_APP_ID'),
//            'options' => [
//                'cluster' => env('PUSHER_APP_CLUSTER'),
//                'host' => env('PUSHER_HOST'),
//                'port' => env('PUSHER_PORT'),
//                'scheme' => env('PUSHER_SCHEME', 'http'),
//                'encrypted' => false,
//                'useTLS' => false,
//            ],
//        ],
//        'pusher' => [
//            'driver' => 'pusher',
//            'key' => env('REVERB_APP_KEY'),
//            'secret' => env('REVERB_APP_SECRET'),
//            'app_id' => env('REVERB_APP_ID'),
//            'options' => [
//                'host' => env('REVERB_HOST'),
//                'port' => env('REVERB_PORT'),
//                'scheme' => env('REVERB_SCHEME', 'http'),
//                'encrypted' => true,
//                'useTLS' => false,
//            ],
//        ],


        'reverb' => [
            'driver' => 'reverb',
            'key' => env('REVERB_APP_KEY'),
            'secret' => env('REVERB_APP_SECRET'),
            'app_id' => env('REVERB_APP_ID'),
            'options' => [
                'host' => env('REVERB_HOST'),
                'port' => env('REVERB_PORT'),
                'scheme' => env('REVERB_SCHEME'),
            ],
        ],

        'ably' => [
            'driver' => 'ably',
            'key' => env('ABLY_KEY'),
        ],
        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
        ],
        'log' => [
            'driver' => 'log',
        ],
        'null' => [
            'driver' => 'null',
        ],

    ],

];
