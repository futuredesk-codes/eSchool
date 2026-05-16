@extends('layouts.master')

@section('title')
{{ __('fees') }} {{__('configration')}}
@endsection


@section('content')
<div class="content-wrapper">
    <div class="page-header">
        <h3 class="page-title">
            {{__('manage')}} {{ __('fees') }} {{__('configration')}}
        </h3>
    </div>
    <div class="row grid-margin">
        <div class="col-lg-12">
            <div class="card">
                <form id="create-fees-config-form" class="fees-config" action="{{ route('fees.config.udpate') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="card-body">
                        <h3 class="card-title">{{ __('payment_gateways') }}</h3>
                        <div class="card-border p-4 mb-4">
                            <h3 class="card-title">{{ __('other') }} {{ __('configration') }}</h3>
                            <hr>
                            
                            <!-- Installment Fees Configuration -->
                            <div class="row mb-4">
                                <div class="col-md-12">
                                    <h5 class="card-title">{{ __('fees') }} {{ __('installment') }} {{ __('configuration') }}</h5>
                                    <div class="form-group">
                                        <label>{{ __('enable') }} {{ __('fees') }} {{ __('installment') }} <span class="text-danger">*</span></label>
                                        <div class="ml-4 d-flex">
                                            <div class="form-check form-check-inline">
                                                <label class="form-check-label">
                                                    <input type="radio" name="enable_fees_installment" class="fees_installment_toggle" value="1" {{ isset($settings['enable_fees_installment']) && $settings['enable_fees_installment'] == '1' ? 'checked' : '' }}>
                                                    {{ __('enable') }}
                                                </label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <label class="form-check-label">
                                                    <input type="radio" name="enable_fees_installment" class="fees_installment_toggle" value="0" {{ isset($settings['enable_fees_installment']) && $settings['enable_fees_installment'] == '0' ? 'checked' : '' }}>
                                                    {{ __('disable') }}
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>{{ __('default') }} {{ __('due') }} {{ __('charges') }} (%) <span class="text-danger">*</span></label>
                                        <input name="default_due_charges" value="{{ isset($settings['default_due_charges']) ? $settings['default_due_charges'] : '5' }}" type="number" min="0" max="100" placeholder="{{ __('default') }} {{ __('due') }} {{ __('charges') }}" class="form-control" />
                                        <span style="color: rgb(0, 55, 107);font-size: 0.8rem" class="ml-2">{{ __('percentage') }} {{ __('of') }} {{ __('total') }} {{ __('amount') }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-3">
                                    <label>{{ __('currency_code') }} <span class="text-danger">*</span></label>
                                    <input name="currency_code" value="{{ isset($settings['currency_code']) ? $settings['currency_code'] : '' }}" type="text" placeholder="{{ __('currency_code') }}" class="form-control" />
                                    <span style="color: rgb(0, 55, 107);font-size: 0.8rem" class="ml-2">{{__('eg_currency_code_inr')}}</span>
                                </div>
                                <div class="form-group col-md-3">
                                    <label>{{ __('currency_symbol') }} <span class="text-danger">*</span></label>
                                    <input name="currency_symbol" value="{{ isset($settings['currency_symbol']) ? $settings['currency_symbol'] : '' }}" type="text" placeholder="{{ __('currency_symbol') }}" class="form-control" />
                                    <span style="color: rgb(0, 55, 107);font-size: 0.8rem" class="ml-2">{{__('eg_currency_symbol_₹')}}</span>
                                </div>
                                <div class="form-group col-md-3">
                                    <label>{{__('compulsory_fee_payment_mode') }}</label> <span class="ml-1 text-danger">*</span>
                                    <div class="ml-4 d-flex">
                                        <div class="form-check form-check-inline">
                                            <label class="form-check-label">
                                                <input type="radio" name="compulsory_fee_payment_mode" class="online_payment_toggle" value="1" {{ isset($settings['compulsory_fee_payment_mode']) && $settings['compulsory_fee_payment_mode'] == '1' ? 'checked' : '' }}>
                                                {{ __('enable') }}
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <label class="form-check-label">
                                                <input type="radio" name="compulsory_fee_payment_mode" class="online_payment_toggle" value="0" {{ isset($settings['compulsory_fee_payment_mode']) && $settings['compulsory_fee_payment_mode'] == '0' ? 'checked' : '' }}>
                                                {{ __('disable') }}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group col-md-3">
                                    <label>{{ __('is_student_can_pay_fees') }} <span class="text-danger">*</span></label>
                                    <div class="ml-4 d-flex">
                                        <div class="form-check form-check-inline">
                                            <label class="form-check-label">
                                                <input type="radio" name="is_student_can_pay_fees" class="online_payment_toggle" value="1" {{ isset($settings['is_student_can_pay_fees']) && $settings['is_student_can_pay_fees'] == '1' ? 'checked' : '' }}>
                                                {{ __('enable') }}
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <label class="form-check-label">
                                                <input type="radio" name="is_student_can_pay_fees" class="online_payment_toggle" value="0" {{ isset($settings['is_student_can_pay_fees']) && $settings['is_student_can_pay_fees'] == '0' ? 'checked' : '' }}>
                                                {{ __('disable') }}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Razorpay Section -->
                            <div class="col-lg-6 mb-4">
                                <div class="card-border">
                                    <h5 class="card-title"><i class="fa fa-angle-double-right menu-icon"></i> {{ __('razorpay') }}</h5>
                                    <hr>
                                    <div class="form-group">
                                        <label>{{ __('currency_code') }} <span class="text-danger">*</span></label>
                                        <input name="razorpay_currency_code" value="{{ isset($settings['razorpay_currency_code']) ? $settings['razorpay_currency_code'] : '' }}" type="text" placeholder="{{ __('currency_code') }}" class="form-control" />
                                        <span style="color: rgb(0, 55, 107);font-size: 0.8rem" class="ml-2">{{__('eg_currency_code_inr')}}</span>
                                    </div>
                                    <div class="form-group">
                                        <label>{{ __('secret_key') }}</label>
                                        <input name="razorpay_secret_key" value="{{ !env('DEMO_MODE') ? ($settings['razorpay_secret_key'] ?? '') : 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx' }}" type="text" placeholder="{{ __('secret_key') }}" class="form-control" />
                                    </div>
                                    <div class="form-group">
                                        <label>{{ __('api_key') }}</label>
                                        <input name="razorpay_api_key" value="{{ !env('DEMO_MODE') ? ($settings['razorpay_api_key'] ?? '') : 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx' }}" type="text" placeholder="{{ __('api_key') }}" class="form-control" />
                                    </div>
                                    <div class="form-group">
                                        <label>{{ __('razoray_webhook_secret') }}</label>
                                        <input name="razorpay_webhook_secret" value="{{ !env('DEMO_MODE') ? ($settings['razorpay_webhook_secret'] ?? '') : 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx' }}" type="text" placeholder="{{ __('razoray_webhook_secret') }}" class="form-control" />
                                    </div>
                                    <div class="form-group">
                                        <label>{{ __('razorpay') }} {{ __('webhook_url') }}</label>
                                        <input name="razorpay_webhook_url" value="{{ !env('DEMO_MODE') ? ($domain.'/webhook/razorpay' ?? '') : 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx' }}" type="text" placeholder="{{ __('webhook_url') }}" class="form-control" readonly />
                                    </div>
                                    <div class="form-group">
                                        <label>{{ __('status') }} <span class="text-danger">*</span></label>
                                        <div class="ml-4 d-flex">
                                            <div class="form-check form-check-inline">
                                                <label class="form-check-label">
                                                    <input type="radio" name="razorpay_status" class="online_payment_toggle" value="1" {{ isset($settings['razorpay_status']) && $settings['razorpay_status'] == '1' ? 'checked' : '' }}>
                                                    {{ __('enable') }}
                                                </label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <label class="form-check-label">
                                                    <input type="radio" name="razorpay_status" class="online_payment_toggle" value="0" {{ isset($settings['razorpay_status']) && $settings['razorpay_status'] == '0' ? 'checked' : '' }}>
                                                    {{ __('disable') }}
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Stripe Section -->
                            <div class="col-lg-6 mb-4">
                                <div class="card-border">
                                    <h5 class="card-title"><i class="fa fa-angle-double-right menu-icon"></i> {{ __('stripe') }}</h5>
                                    <hr>
                                    <div class="form-group">
                                        <label>{{ __('currency_code') }} <span class="text-danger">*</span></label>
                                        <input name="stripe_currency_code" value="{{ isset($settings['stripe_currency_code']) ? $settings['stripe_currency_code'] : '' }}" type="text" placeholder="{{ __('currency_code') }}" class="form-control" />
                                        <span style="color: rgb(0, 55, 107);font-size: 0.8rem" class="ml-2">{{__('eg_currency_code_inr')}}</span>
                                    </div>
                                    <div class="form-group">
                                        <label>{{ __('stripe_publishable_key') }}</label>
                                        <input name="stripe_publishable_key" value="{{ !env('DEMO_MODE') ? ($settings['stripe_publishable_key'] ?? '') : 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx' }}" type="text" placeholder="{{ __('stripe_publishable_key') }}" class="form-control" />
                                    </div>
                                    <div class="form-group">
                                        <label>{{ __('stripe_secret_key') }}</label>
                                        <input name="stripe_secret_key" value="{{ !env('DEMO_MODE') ? ($settings['stripe_secret_key'] ?? '') : 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx' }}" type="text" placeholder="{{ __('stripe_secret_key') }}" class="form-control" />
                                    </div>
                                    <div class="form-group">
                                        <label>{{ __('stripe_webhook_secret') }}</label>
                                        <input name="stripe_webhook_secret" value="{{ !env('DEMO_MODE') ? ($settings['stripe_webhook_secret'] ?? '') : 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx' }}" type="text" placeholder="{{ __('stripe_webhook_secret') }}" class="form-control" />
                                    </div>
                                    <div class="form-group">
                                        <label>{{ __('stripe') }} {{ __('webhook_url') }}</label>
                                        <input name="stripe_webhook_url" value="{{ !env('DEMO_MODE') ? ($domain.'/webhook/stripe' ?? '') : 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx' }}" type="text" placeholder="{{ __('webhook_url') }}" class="form-control" readonly />
                                    </div>
                                    <div class="form-group">
                                        <label>{{ __('status') }} <span class="text-danger">*</span></label>
                                        <div class="ml-4 d-flex">
                                            <div class="form-check form-check-inline">
                                                <label class="form-check-label">
                                                    <input type="radio" name="stripe_status" class="online_payment_toggle" value="1" {{ isset($settings['stripe_status']) && $settings['stripe_status'] == '1' ? 'checked' : '' }}>
                                                    {{ __('enable') }}
                                                </label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <label class="form-check-label">
                                                    <input type="radio" name="stripe_status" class="online_payment_toggle" value="0" {{ isset($settings['stripe_status']) && $settings['stripe_status'] == '0' ? 'checked' : '' }}>
                                                    {{ __('disable') }}
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                             <!-- Paystack Section -->
                            <div class="col-lg-6 mb-4">
                                <div class="card-border">
                                    <h5 class="card-title"><i class="fa fa-angle-double-right menu-icon"></i> {{__('paystack')}}</h5>
                                    <hr>
                                    <div class="form-group">
                                        <label>{{ __('currency_code') }} <span class="text-danger">*</span></label>
                                        <input name="paystack_currency_code" value="{{ isset($settings['paystack_currency_code']) ? $settings['paystack_currency_code'] : '' }}" type="text" placeholder="{{ __('currency_code') }}" class="form-control" />
                                        <span style="color: rgb(0, 55, 107);font-size: 0.8rem" class="ml-2">{{__('eg_currency_code_inr')}}</span>
                                    </div>
                                    <div class="form-group">
                                        <label>{{ __('paystack_public_key') }}</label>
                                        <input name="paystack_public_key" value="{{ !env('DEMO_MODE') ? (isset($settings['paystack_public_key']) ? $settings['paystack_public_key'] : '') : 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx' }}" type="text" placeholder="{{ __('paystack_public_key') }}" class="form-control" />
                                    </div>
                                    <div class="form-group">
                                        <label>{{ __('paystack_secret_key') }}</label>
                                        <input name="paystack_secret_key" value="{{ !env('DEMO_MODE') ? (isset($settings['paystack_secret_key']) ? $settings['paystack_secret_key'] : '') : 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx' }}" type="text" placeholder="{{ __('paystack_secret_key') }}" class="form-control" />
                                    </div>
                                    <div class="form-group">
                                        <label>{{__('paystack')}} {{ __('webhook_url') }}</label>
                                        <input name="paystack_webhook_url"  value="{{ !env('DEMO_MODE') ? (isset($domain) ? $domain.'/webhook/paystack' : '' ) : 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'}}" type="text" placeholder="{{ __('paystack').' '.__('webhook_url')}}"  class="form-control" readonly/>
                                    </div>
                                    <div class="form-group">
                                        <label>{{__('paystack')}} {{ __('payment_url') }}</label>
                                        <input name="paystack_payment_url"  value="{{ !env('DEMO_MODE') ? (isset($domain) ? 'https://api.paystack.co': '' ) : 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'}}" type="text" placeholder="{{ __('paystack').' '.__('payment_url')}}"  class="form-control" readonly/>
                                    </div>
                                    <div class="form-group">
                                        <label>{{ __('status') }} <span class="text-danger">*</span></label>
                                        <div class="ml-4 d-flex">
                                            <div class="form-check form-check-inline">
                                                <label class="form-check-label">
                                                    <input type="radio" name="paystack_status" class="online_payment_toggle" value="1" {{ isset($settings['paystack_status']) && $settings['paystack_status'] == '1' ? 'checked' : '' }}>
                                                    {{ __('enable') }}
                                                </label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <label class="form-check-label">
                                                    <input type="radio" name="paystack_status" class="online_payment_toggle" value="0" {{ isset($settings['paystack_status']) && $settings['paystack_status'] == '0' ? 'checked' : '' }}>
                                                    {{ __('disable') }}
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Flutterwave Section -->
                            <div class="col-lg-6 mb-4">
                                <div class="card-border">
                                    <h5 class="card-title"><i class="fa fa-angle-double-right menu-icon"></i> {{__('flutterwave')}}</h5>
                                    <hr>
                                    <div class="form-group">
                                        <label>{{ __('currency_code') }} <span class="text-danger">*</span></label>
                                        <input name="flutterwave_currency_code" value="{{ isset($settings['flutterwave_currency_code']) ? $settings['flutterwave_currency_code'] : '' }}" type="text" placeholder="{{ __('currency_code') }}" class="form-control" />
                                        <span style="color: rgb(0, 55, 107);font-size: 0.8rem" class="ml-2">{{__('eg_currency_code_inr')}}</span>
                                    </div>
                                    <div class="form-group">
                                        <label>{{ __('flutterwave_public_key') }}</label>
                                        <input name="flutterwave_public_key" value="{{ !env('DEMO_MODE') ? (isset($settings['flutterwave_public_key']) ? $settings['flutterwave_public_key'] : '') : 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx' }}" type="text" placeholder="{{ __('flutterwave_public_key') }}" class="form-control" />
                                    </div>
                                    <div class="form-group">
                                        <label>{{ __('flutterwave_secret_key') }}</label>
                                        <input name="flutterwave_secret_key" value="{{ !env('DEMO_MODE') ? (isset($settings['flutterwave_secret_key']) ? $settings['flutterwave_secret_key'] : '') : 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx' }}" type="text" placeholder="{{ __('flutterwave_secret_key') }}" class="form-control" />
                                    </div>
                                    <div class="form-group">
                                        <label>{{ __('flutterwave_encryption_key') }}</label>
                                        <input name="flutterwave_hash_key" value="{{ !env('DEMO_MODE') ? (isset($settings['flutterwave_hash_key']) ? $settings['flutterwave_hash_key'] : '') : 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx' }}" type="text" placeholder="{{ __('flutterwave_encryption_key') }}" class="form-control" />
                                    </div>
                                    <div class="form-group">
                                        <label>{{__('flutterwave')}} {{ __('webhook_url') }}</label>
                                        <input name="flutterwave_webhook_url"  value="{{ !env('DEMO_MODE') ? (isset($domain) ? $domain.'/webhook/flutterwave' : '' ) : 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'}}" type="text" placeholder="{{ __('flutterwave').' '.__('webhook_url')}}"  class="form-control" readonly/>
                                    </div>
                                    <div class="form-group">
                                        <label>{{ __('status') }} <span class="text-danger">*</span></label>
                                        <div class="ml-4 d-flex">
                                            <div class="form-check form-check-inline">
                                                <label class="form-check-label">
                                                    <input type="radio" name="flutterwave_status" class="online_payment_toggle" value="1" {{ isset($settings['flutterwave_status']) && $settings['flutterwave_status'] == '1' ? 'checked' : '' }}>
                                                    {{ __('enable') }}
                                                </label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <label class="form-check-label">
                                                    <input type="radio" name="flutterwave_status" class="online_payment_toggle" value="0" {{ isset($settings['flutterwave_status']) && $settings['flutterwave_status'] == '0' ? 'checked' : '' }}>
                                                    {{ __('disable') }}
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <input class="btn btn-theme mt-5" type="submit" value="{{ __('Submit') }}">
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection
