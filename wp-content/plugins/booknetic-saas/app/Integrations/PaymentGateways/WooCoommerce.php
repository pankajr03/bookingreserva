<?php

namespace BookneticSaaS\Integrations\PaymentGateways;

use BookneticApp\Providers\Core\Route;
use BookneticSaaS\Models\Tenant;
use BookneticSaaS\Models\TenantBilling;
use BookneticSaaS\Providers\Helpers\Helper;

class WooCoommerce
{
    public static function initFilters()
    {
        if (! class_exists('woocommerce')) {
            return;
        }

        $productId = self::bookneticProduct();

        if (! is_wp_error(wp_set_post_terms($productId, ['exclude-from-search', 'exclude-from-catalog'], 'product_visibility', false))) {
            do_action('woocommerce_product_set_visibility', $productId, "hidden");
        }

        add_filter('woocommerce_cart_item_name', [ self::class, 'getItemName' ], 10, 2);
        add_filter('woocommerce_cart_item_price', [ self::class, 'getItemPrice' ], 10, 3);
        add_action('woocommerce_before_calculate_totals', [ self::class, 'beforeCalculateTotals' ], 10, 1);
        add_action('woocommerce_checkout_create_order_line_item', [ self::class, 'checkoutCreateOrderLineItem' ], 10, 4);
        add_action('woocommerce_order_status_changed', [ self::class, 'orderStatusChanged' ], 10, 3);
        add_filter('woocommerce_checkout_cart_item_quantity', [ self::class, 'checkoutCartItemQuantity' ], 10, 2);
        add_filter('woocommerce_order_item_quantity_html', [ self::class, 'checkoutCartItemQuantity' ], 10, 2);
        add_filter('woocommerce_display_item_meta', [ self::class, 'displayItemMeta' ], 10, 3);
        add_action('woocommerce_thankyou', [ self::class, 'redirectAfterCheckout' ]);
    }

    public static function getItemName($itemName, $cartItem)
    {
        if (isset($cartItem['booknetic_billing_id'])) {
            $itemName = bkntcsaas__('Adding deposit');
        }

        return $itemName;
    }

    public static function getItemPrice($productPrice, $cartItem, $cartItemKey)
    {
        if (isset($cartItem['booknetic_billing_id'])) {
            $billing = TenantBilling::get($cartItem['booknetic_billing_id']);
            if ($billing) {
                $productPrice = wc_price($billing->amount);
            }
        }

        return $productPrice;
    }

    public static function beforeCalculateTotals($cartObject)
    {
        foreach ($cartObject->cart_contents as $cartItem) {
            if (isset($cartItem['booknetic_billing_id'])) {
                $billing = TenantBilling::get($cartItem['booknetic_billing_id']);
                if ($billing) {
                    $cartItem[ 'data' ]->set_price($billing->amount);
                }
            }
        }
    }

    public static function checkoutCreateOrderLineItem($item, $cartItem_key, $values, $order)
    {
        if (isset($values[ 'booknetic_billing_id' ])) {
            $item->update_meta_data('booknetic_billing_id', $values[ 'booknetic_billing_id' ]);
        }
    }

    public static function orderStatusChanged($orderId, $from, $to)
    {
        $wcOrderStatuses = Helper::getOption('woocommerce_tenant_order_statuses', 'wc-processing,wc-on-hold,wc-completed', false);
        $wcOrderStatuses = explode(',', $wcOrderStatuses);
        $wcPendingStatuses = explode(',', Helper::getOption('woocommerce_pending_order_statuses', 'wc-pending', false));

        if (in_array('wc-' . $to, $wcOrderStatuses)) {
            $paymentStatus = 'paid';
        } elseif (in_array('wc-' . $to, $wcPendingStatuses)) {
            $paymentStatus = 'pending';
        } else {
            return;
        }

        $order = new \WC_Order($orderId);

        foreach ($order->get_items() as $itemId => $orderItem) {
            $billingId = wc_get_order_item_meta($itemId, 'booknetic_billing_id');

            if ($billingId > 0 && ($billingInf = TenantBilling::noTenant()->get($billingId))) {
                TenantBilling::noTenant()->where('id', $billingId)->update([ 'status' => $paymentStatus ]);

                $amount     = $billingInf->amount;
                $tenantId   = $billingInf->tenant_id;
                $tenantInf  = Tenant::get($tenantId);

                if ($paymentStatus == 'paid') {
                    Tenant::where('id', $tenantId)->update([
                        'user_id' => $tenantInf->user_id,
                        'plan_id' => $tenantInf->plan_id,
                        'expires_in' => $tenantInf->expires_in,
                        'money_balance' =>  Helper::floor($tenantInf->money_balance) + Helper::floor($amount)
                    ]);
                    do_action('bkntcsaas_tenant_deposit_paid', $billingId);
                }

                do_action('bkntcsaas_tenant_deposit_added', $billingId);
            }
        }
    }

    public static function checkoutCartItemQuantity($quantity, $item)
    {
        if (isset($item[ 'booknetic_billing_id' ])) {
            $quantity = '';
        }

        return $quantity;
    }

    public static function displayItemMeta($html, $item, $args)
    {
        if (isset($item[ 'booknetic_billing_id' ])) {
            $html = '';
        }

        return $html;
    }

    public static function redirectAfterCheckout($order_id)
    {
        if (function_exists('is_order_received_page')  && is_order_received_page()) {
            $order = wc_get_order($order_id);

            $order_status = $order->get_status();
            $payment_status = 'cancel';
            $wcPendingStatuses = explode(',', Helper::getOption('woocommerce_pending_order_statuses', 'wc-pending', false));

            if (in_array('wc-' . $order_status, $wcPendingStatuses)) {
                $payment_status = 'pending';
            }

            foreach ($order->get_items() as $itemId => $item) {
                $billingId = wc_get_order_item_meta($itemId, 'booknetic_billing_id');
                $billingInf = TenantBilling::noTenant()->get($billingId);

                if (! isset($item['booknetic_billing_id'])) {
                    continue;
                }

                if ($billingId > 0 && $billingInf && $billingInf->status === 'paid') {
                    $payment_status = 'success';
                }

                Helper::redirect(Route::getURL('billing') . '&payment_status=' . $payment_status);
            }
        }
    }

    public static function bookneticProduct()
    {
        if (! class_exists('WooCommerce')) {
            return 0;
        }

        $productId = Helper::getOption('woocommerce_tenant_product_id', null, false);

        if ($productId) {
            $productInf = wc_get_product($productId);
        }

        if (! $productId || ! $productInf || ! $productInf->exists() || $productInf->get_status() !== 'publish') {
            $productId = wp_insert_post([
                'post_title'  => 'Booknetic SaaS',
                'post_type'   => 'product',
                'post_status' => 'publish'
            ]);

            Helper::setOption('woocommerce_tenant_product_id', $productId, false);

            // set product is simple/variable/grouped
            wp_set_object_terms($productId, 'simple', 'product_type');

            update_post_meta($productId, '_visibility', 'hidden');
            update_post_meta($productId, '_stock_status', 'instock');
            update_post_meta($productId, 'total_sales', '0');
            update_post_meta($productId, '_downloadable', 'no');
            update_post_meta($productId, '_virtual', 'yes');
            update_post_meta($productId, '_regular_price', '0');
            update_post_meta($productId, '_sale_price', '');
            update_post_meta($productId, '_purchase_note', '');
            update_post_meta($productId, '_featured', 'no');
            update_post_meta($productId, '_weight', '');
            update_post_meta($productId, '_length', '');
            update_post_meta($productId, '_width', '');
            update_post_meta($productId, '_height', '');
            update_post_meta($productId, '_sku', '');
            update_post_meta($productId, '_product_attributes', []);
            update_post_meta($productId, '_sale_price_dates_from', '');
            update_post_meta($productId, '_sale_price_dates_to', '');
            update_post_meta($productId, '_price', '0');
            update_post_meta($productId, '_sold_individually', 'yes');
            update_post_meta($productId, '_manage_stock', 'no');
            update_post_meta($productId, '_backorders', 'no');
            wc_update_product_stock($productId, 0, 'set');
            update_post_meta($productId, '_stock', '');
        }

        return $productId;
    }

    private static function setCustomerCookie()
    {
        if (WC()->session && WC()->session instanceof \WC_Session_Handler && WC()->session->get_session_cookie() === false) {
            WC()->session->set_customer_session_cookie(true);
        }
    }

    public static function emptyBookneticProductsFromWoocommerce(): void
    {
        if (! class_exists('WooCommerce')) {
            return;
        }

        wc_load_cart();

        if (
            WC()->session &&
            WC()->session instanceof \WC_Session_Handler &&
            WC()->session->get_session_cookie() === false
        ) {
            WC()->session->set_customer_session_cookie(true);
        }

        $tenantProductId = Helper::getOption('woocommerce_tenant_product_id', null, false);
        $productId       = Helper::getOption('woocommerce_product_id', null, false);

        if (empty($tenantProductId) && empty($productId)) {
            return;
        }

        foreach (WC()->cart->get_cart() as $cartItem_key => $cartItem) {
            $id = $cartItem['product_id'];

            if (
                (isset($cartItem['booknetic_billing_id'])) ||
                (isset($cartItem['booknetic_custom_data'])) ||
                (isset($cartItem['booknetic_item'])) ||
                ($id == $tenantProductId) ||
                ($id == $productId)
            ) {
                WC()->cart->remove_cart_item($cartItem_key);
            }
        }
    }

    public static function addToCart($billingId)
    {
        wc_load_cart();

        self::setCustomerCookie();
        self::emptyBookneticProductsFromWoocommerce();
        WC()->cart->add_to_cart(self::bookneticProduct(), 1, '', [], [
            'booknetic_billing_id'  =>  $billingId
        ]);
    }

    public static function redirect()
    {
        return Helper::getOption('woocommerce_tenant_redirect_to', 'cart', false) === 'cart' ? wc_get_cart_url() : wc_get_checkout_url();
    }
}
