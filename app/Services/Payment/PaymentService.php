<?php

namespace App\Services\Payment;

use InvalidArgumentException;
use App\Models\PaymentConfiguration;
use App\Services\Payment\StripePayment;
use App\Services\Payment\PaystackPayment;
use App\Services\Payment\RazorpayPayment;
use App\Services\Payment\FlutterwavePayment;

class PaymentService {
    /**
     * @param string $paymentGateway - Stripe
     * @return StripePayment
     */
    public static function create($payment_method) {
        $setting_currency_code = getSettings('currency_code');
        $currency_code = $setting_currency_code['currency_code'];

        $default_currency_code = strtoupper($currency_code);

        switch ($payment_method) {
            case 1: // Razorpay
                $razorpay_setting_api_key = getSettings('razorpay_api_key');
                $razorpay_api_key = $razorpay_setting_api_key['razorpay_api_key'];
                $razorpay_setting_secret_key = getSettings('razorpay_secret_key');
                $razorpay_secret_key = $razorpay_setting_secret_key['razorpay_secret_key'];

                $setting_currency_code = getSettings('razorpay_currency_code');
                $currency_code = $setting_currency_code['razorpay_currency_code'] ?? $default_currency_code;

                $currency_code = strtoupper($currency_code);

                return new RazorpayPayment($razorpay_secret_key, $razorpay_api_key, $currency_code);
            case 2: // Stripe
                $stripe_setting_secret_key = getSettings('stripe_secret_key');
                $stripe_secret_key = $stripe_setting_secret_key['stripe_secret_key'];

                $setting_currency_code = getSettings('stripe_currency_code');
                $currency_code = $setting_currency_code['stripe_currency_code']  ?? $default_currency_code;

                $currency_code = strtoupper($currency_code);

                return new StripePayment($stripe_secret_key, $currency_code);
            case 3: // Paystack

                $setting_currency_code = getSettings('paystack_currency_code');
                $currency_code = $setting_currency_code['paystack_currency_code']  ?? $default_currency_code;

                $currency_code = strtoupper($currency_code);

                return new PaystackPayment($currency_code);
            case 4: //Flutterwave

                $setting_currency_code = getSettings('flutterwave_currency_code');
                $currency_code = $setting_currency_code['flutterwave_currency_code']  ?? $default_currency_code;

                $currency_code = strtoupper($currency_code);

                $flutterwave_setting_secret_key = getSettings('flutterwave_secret_key');
                $flutterwave_secret_key = $flutterwave_setting_secret_key['flutterwave_secret_key'];

                return new FlutterwavePayment($currency_code, $flutterwave_secret_key);
            default:
                throw new \Exception("Invalid payment method selected");
        }
    }
}
