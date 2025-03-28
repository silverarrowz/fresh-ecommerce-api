<?php

return [
    'key' => env('STRIPE_PUBLISHABLE_KEY'),
    'secret' => env('STRIPE_SECRET'),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
];
