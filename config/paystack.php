<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Paystack Public Key
    |--------------------------------------------------------------------------
    |
    | Your Paystack public key from your dashboard
    |
    */
    'publicKey' => env('PAYSTACK_PUBLIC_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Paystack Secret Key
    |--------------------------------------------------------------------------
    |
    | Your Paystack secret key from your dashboard
    |
    */
    'secretKey' => env('PAYSTACK_SECRET_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Paystack Payment URL
    |--------------------------------------------------------------------------
    |
    | The base URL for Paystack API
    |
    */
    'paymentUrl' => env('PAYSTACK_PAYMENT_URL', 'https://api.paystack.co'),

    /*
    |--------------------------------------------------------------------------
    | Paystack Merchant Email
    |--------------------------------------------------------------------------
    |
    | Your Paystack merchant email
    |
    */
    'merchantEmail' => env('PAYSTACK_MERCHANT_EMAIL', ''),
];
