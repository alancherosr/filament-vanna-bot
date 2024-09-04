<?php

// config for Alancherosr/FilamentVannaBot
return [
    'enable' => true,

    'botname' => env('BOTNAME'),

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'organization' => env('OPENAI_ORGANIZATION'),
    ],
    
    'proxy'=> env('OPENAI_PROXY'),

];