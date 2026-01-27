<?php

namespace BookneticApp\Backend\Boostore;

use BookneticApp\Models\Cart;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Backend\Boostore\Helpers\BoostoreHelper;
use BookneticApp\Providers\IoC\Container;
use BookneticApp\Providers\FSCode\Services\FSCodeApiService;

class Controller extends \BookneticApp\Providers\Core\Controller
{
    public function index()
    {
        $this->view('index', [
            'categories' => BoostoreHelper::get('categories'),
            'cart_items_count' => Cart::where('active', 1)->count()
        ]);
    }

    public function details()
    {
        $addonSlug = Helper::_get('slug', '', 'string');

        $addon = BoostoreHelper::get('info/' . $addonSlug);

        if (empty($addon) || !isset($addon['slug'])) {
            $this->view('modal/addons_v2');
        }

        $cartItems = Cart::select('slug')->where('active', 1)->fetchAll();

        $cartItems = array_column($cartItems, 'slug');

        $addon[ 'is_installed' ] = ! empty(BoostoreHelper::getAddonSlug($addon[ 'slug' ])) && file_exists(realpath(WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . BoostoreHelper::getAddonSlug($addon[ 'slug' ])));

        $addon[ 'in_cart' ] = in_array($addon['slug'], $cartItems, true) && ! $addon[ 'is_installed' ];

        $this->view('details', [
            'addon'     => $addon
        ]);
    }

    public function purchased(): void
    {
        Container::get(FSCodeApiService::class)->sync();
        Cart::delete();

        $this->view('purchased', [], false);
    }

    public function my_purchases(): void
    {
        $myPurchases = BoostoreHelper::get('my_purchases');

        $this->view('my_purchases', [
            'items' => $myPurchases['invoices'],
            'is_migration' => ! empty(Helper::getOption('migration_v3', false, false)),
            'cart_items_count' => Cart::where('active', 1)->count()
        ]);
    }

    public function my_addons(): void
    {
        $myPurchases = BoostoreHelper::get('my_addons');

        foreach ($myPurchases['addons'] as $i => $addon) {
            $myPurchases['addons'][$i]['is_installed'] = BoostoreHelper::isInstalled($addon['slug']);
        }

        $this->view('my_addons', [
            'items' => $myPurchases['addons'],
            'is_migration' => ! empty(Helper::getOption('migration_v3', false, false)),
            'cart_items_count' => Cart::where('active', 1)->count()
        ]);
    }
}
