<?php

return [
    /*
	|--------------------------------------------------------------------------
	| MyceliumGear authorization configuration
	|--------------------------------------------------------------------------
    */
    'gateway_url' => env('GEAR_GATEWAY_URL', ''),
    'gateway_id' => env('GEAR_GATEWAY_ID',''),
    'gateway_secret' => env('GEAR_GATEWAY_SECRET',''),

    /*
	|--------------------------------------------------------------------------
	| Default configuration
	|--------------------------------------------------------------------------
	|
	| Define default callback data that will be used before any custom data are set
    */
    'defaults' => [
        'callback_data' => [
            //'foo' => 'bar'
        ]
    ],
];
