<?php

/*
 * This file is part of the Laravel Paystack package.
 *
 * (c) Prosper Otemuyiwa <prosperotemuyiwa@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

return [

    /**
     * Public Key From Paystack Dashboard
     *
     */
    'publicKey' => env('PAYSTACK_PUBLIC_KEY'),

    /**
     * Secret Key From Paystack Dashboard
     *
     */
    'secretKey' => env('PAYSTACK_SECRET_KEY'),

    /**
     * Paystack Payment URL
     *
     */
    'paymentUrl' => env('PAYSTACK_PAYMENT_URL'),

    /**
     * Optional email address of the merchant
     *
     */
    'merchantEmail' => env('MERCHANT_EMAIL'),

    'payment_page_url' => env('PAYSTACK_PAYMENT_PAGE_URL', 'https://paystack.com/pay/swifter'),

    'accounts' => [
        'default' => [
            'public_key' => env('PAYSTACK_PUBLIC_KEY'),
            'secret_key' => env('PAYSTACK_SECRET_KEY'),
        ],
        'fasttrack' => [
            'public_key' => env('FASTTRACK_PAYSTACK_PUBLIC_KEY'),
            'secret_key' => env('FASTTRACK_PAYSTACK_SECRET_KEY'),
        ],
    ],

    'webhook_ips' => [
        '52.31.139.75',
        '52.49.173.169',
        '52.214.14.220',
    ],

];
