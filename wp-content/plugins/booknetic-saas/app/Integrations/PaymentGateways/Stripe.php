<?php

namespace BookneticSaaS\Integrations\PaymentGateways;

use Stripe\Price;
use Stripe\Coupon;
use Stripe\Webhook;
use Stripe\Product;
use Stripe\Subscription;
use Stripe\Checkout\Session;
use BookneticSaaS\Models\Tenant;
use Stripe\Exception\SignatureVerificationException;
use BookneticSaaS\Providers\Helpers\Helper;
use BookneticSaaS\Models\Plan;

class Stripe
{
    private $_paymentId;
    private $_price;
    private $_first_price;
    private $_currency;
    private $_payment_cycle;
    private $_plan;
    private $_successURL;
    private $_cancelURL;
    private $_email;

    public static function webhookUrl()
    {
        return site_url() . '/?booknetic_saas_action=stripe_webhook';
    }

    public function __construct()
    {
        \Stripe\Stripe::setApiKey(Helper::getOption('stripe_client_secret'));
        \Stripe\Stripe::setApiVersion('2025-07-30.basil');
    }

    public function setId($paymentId)
    {
        $this->_paymentId = $paymentId;

        return $this;
    }

    public function setCycle($cycle)
    {
        $this->_payment_cycle = $cycle === 'monthly' ? 'month' : 'year';

        return $this;
    }

    public function setAmount($price, $first_price, $currency = 'USD')
    {
        $this->_price       = $price;
        $this->_first_price = $first_price;
        $this->_currency    = $currency;

        return $this;
    }

    public function setPlan($plan)
    {
        $this->_plan = $plan;

        return $this;
    }

    public function setEmail($email)
    {
        $this->_email = $email;

        return $this;
    }

    public function setSuccessURL($url)
    {
        $this->_successURL = $url;

        return $this;
    }

    public function setCancelURL($url)
    {
        $this->_cancelURL = $url;

        return $this;
    }

    public function createRecurringPayment()
    {
        if (isset($this->_plan->reset_stripe_data)) {
            $this->_plan->stripe_product_data = null;
        }

        try {
            $coupon = $this->getCoupon();

            $sessionArray = [
                'success_url'           => $this->_successURL,
                'cancel_url'            => $this->_cancelURL,
                'payment_method_types'  => [ 'card' ],
                'mode'                  => 'subscription',
                'line_items'            => [ [ 'price' => $this->getPriceId(), 'quantity' => 1 ] ],
                'subscription_data'     => [ 'metadata' => [ 'billing_id' => $this->_paymentId ] ],
                'customer_email'        => $this->_email
            ];

            if (!empty($coupon)) {
                $sessionArray['discounts'] = [ [ 'coupon' => $coupon ]] ;
            }

            $checkout_session = Session::create($sessionArray);
        } catch (\Exception $e) {
            return [
                'status'    => false,
                'error'     => $e->getMessage()
            ];
        }

        return [
            'status'    => true,
            'id'        => $checkout_session->id
        ];
    }

    public function checkSession($sessionId)
    {
        try {
            $sessionInf = Session::retrieve($sessionId);
        } catch (\Exception $e) {
            return [
                'status'    => false,
                'error'     => $e->getMessage()
            ];
        }

        if (
            !(
                isset($sessionInf->payment_status, $sessionInf->mode, $sessionInf->subscription) && $sessionInf->payment_status === 'paid' && $sessionInf->mode === 'subscription' && !empty($sessionInf->subscription) && is_string($sessionInf->subscription)
            )
        ) {
            return [
                'status'    => false,
                'error'     => 'Error!'
            ];
        }

        try {
            $subscriptionInf = Subscription::retrieve($sessionInf->subscription);
        } catch (\Exception $e) {
            return [
                'status'    => false,
                'error'     => $e->getMessage()
            ];
        }

        return [
            'status'        => true,
            'subscription'  => $sessionInf->subscription,
            'billing_id'    => $subscriptionInf->metadata->billing_id
        ];
    }

    public function cancelSubscription($subscriptionId)
    {
        try {
            $subscriptionInf = Subscription::retrieve($subscriptionId);
            $subscriptionInf->cancel();
        } catch (\Exception $e) {
            return [
                'status'    => false,
                'error'     => $e->getMessage()
            ];
        }

        return [ 'status' => true ];
    }

    public function webhook()
    {
        $payload = @file_get_contents("php://input");

        $endpoint_secret = Helper::getOption('stripe_webhook_secret', '');

        if (empty($endpoint_secret) || !isset($_SERVER["HTTP_STRIPE_SIGNATURE"])) {
            http_response_code(400);
            exit();
        }

        $sig_header = $_SERVER["HTTP_STRIPE_SIGNATURE"];

        try {
            $event = Webhook::constructEvent(
                $payload,
                $sig_header,
                $endpoint_secret
            );
        } catch (\UnexpectedValueException $e) {
            // Invalid payload
            http_response_code(400);
            exit();
        } catch (SignatureVerificationException $e) {
            // Invalid signature
            http_response_code(400);
            exit();
        }

        if ($event->type === 'invoice.paid') {
            $this->subscriptionPaid($event);
        } elseif ($event->type === 'customer.subscription.deleted') {
            if (isset($event->data->object->id) && is_string($event->data->object->id) && !empty($event->data->object->id)) {
                Tenant::unsubscribed($event->data->object->id);
            }
        }

        http_response_code(200);
    }

    private function subscriptionPaid($event): void
    {
        $invoice = $event->data->object;

        if (!isset($invoice->status) || $invoice->status !== 'paid') {
            http_response_code(400);
            exit;
        }

        if (isset($invoice->billing_reason) && $invoice->billing_reason === 'manual') {
            http_response_code(200);

            return;
        }

        $subscriptionId = null;
        if (isset($invoice->parent->subscription_details->subscription) && is_string($invoice->parent->subscription_details->subscription)) {
            $subscriptionId = $invoice->parent->subscription_details->subscription;
        } elseif (
            isset($invoice->lines->data[0]->parent->subscription_item_details->subscription) && is_array($invoice->lines->data) && !empty($invoice->lines->data) && is_string($invoice->lines->data[0]->parent->subscription_item_details->subscription)
        ) {
            $subscriptionId = $invoice->lines->data[0]->parent->subscription_item_details->subscription;
        } elseif (is_string($invoice->subscription) && !empty($invoice->subscription)) {
            $subscriptionId = $invoice->subscription;
        }

        if (empty($subscriptionId)) {
            http_response_code(400);
            exit;
        }

        $billingId = $invoice->parent->subscription_details->metadata->billing_id ?? null;

        if (isset($invoice->lines->data) && empty($billingId) && is_array($invoice->lines->data) && !empty($invoice->lines->data)) {
            $firstLine = $invoice->lines->data[0];
            if (isset($firstLine->metadata->billing_id)) {
                $billingId = $firstLine->metadata->billing_id;
            }
        }

        if (empty($billingId)) {
            try {
                $subscription = Subscription::retrieve($subscriptionId);
            } catch (\Exception $e) {
                http_response_code(400);
                exit;
            }

            if (isset($subscription->metadata->billing_id) && !empty($subscription->metadata->billing_id)) {
                $billingId = $subscription->metadata->billing_id;
            }
        }

        if (empty($billingId)) {
            http_response_code(400);
            exit;
        }

        if (!Tenant::paymentSucceded($subscriptionId, $billingId)) {
            http_response_code(400);
            exit;
        }
    }

    public function getPriceData()
    {
        if (empty($this->_plan->stripe_product_data)) {
            $product = Product::create([ 'name' => $this->_plan->name ]);

            $monthly_price = Price::create([
                'product'       => $product->id,
                'unit_amount'   => $this->normalizePrice($this->_plan->monthly_price, $this->_currency),
                'currency'      => $this->_currency,
                'recurring'     => [ 'interval' => 'month' ]
            ]);

            $annually_price = Price::create([
                'product'       => $product->id,
                'unit_amount'   => $this->normalizePrice($this->_plan->annually_price, $this->_currency),
                'currency'      => $this->_currency,
                'recurring'     => [ 'interval' => 'year' ]
            ]);

            $stripe_product_data = [
                'id'            => $product->id,
                'month'         => $monthly_price->id,
                'year'          => $annually_price->id,
                'month_coupon'  => '',
                'year_coupon'   => ''
            ];

            if ($this->_plan->monthly_price_discount > 0 && $this->_plan->monthly_price_discount <= 100) {
                $coupon = Coupon::create([
                    'name'          => $this->_plan->monthly_price_discount . '% OFF',
                    'duration'      => 'once',
                    'percent_off'   => $this->_plan->monthly_price_discount
                ]);

                $stripe_product_data['month_coupon'] = $coupon->id;
            }

            if ($this->_plan->annually_price_discount > 0 && $this->_plan->annually_price_discount <= 100) {
                $coupon = Coupon::create([
                    'name'          => $this->_plan->annually_price_discount . '% OFF',
                    'duration'      => 'once',
                    'percent_off'   => $this->_plan->annually_price_discount
                ]);

                $stripe_product_data['year_coupon'] = $coupon->id;
            }

            $this->_plan->stripe_product_data = json_encode($stripe_product_data);

            Plan::where('id', $this->_plan->id)->update([ 'stripe_product_data' => $this->_plan->stripe_product_data ]);
        }

        return $this->_plan->stripe_product_data;
    }

    public function getPriceId()
    {
        $priceData = json_decode($this->getPriceData(), true);

        return $priceData[ $this->_payment_cycle ];
    }

    public function getCoupon()
    {
        $priceData = json_decode($this->getPriceData(), true);

        return $priceData[ $this->_payment_cycle . '_coupon' ];
    }

    private function normalizePrice($price, $currency)
    {
        $zeroDecimalCurrencies = [ 'BIF', 'DJF', 'JPY', 'KRW', 'PYG', 'VND', 'XAF', 'XPF', 'CLP', 'GNF', 'KMF', 'MGA', 'RWF', 'VUV', 'XOF' ];

        if (in_array($currency, $zeroDecimalCurrencies)) {
            return $price;
        }

        return round($price * 100);
    }

    public function updateProductName($id, $name)
    {
        try {
            Product::update($id, [ 'name' => $name ]);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }
}
