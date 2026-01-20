<?php

namespace BookneticApp\Backend\Boostore;

use BookneticApp\Backend\Boostore\Helpers\BoostoreHelper;
use BookneticApp\Models\Cart;

class CartController extends \BookneticApp\Providers\Core\Controller
{
    public function index()
    {
        $cartItems = Cart::select('slug')->where('active', 1)->fetchAll();

        if (Cart::where('active', 0)->count() > 100) {
            //todo: add analytics before deleting
            Cart::where('active', 0)->delete();
        }

        $cartItems = array_column($cartItems, 'slug');

        $cartAddonData = [];

        foreach (BoostoreHelper::getAllAddons()[ 'items' ] as $addon) {
            if (!in_array($addon[ 'slug' ], $cartItems)) {
                continue;
            }

            if ($addon[ 'purchase_status' ] === 'owned') {
                Cart::where([ 'slug' => $addon[ 'slug' ], 'active' => 1 ])->delete();
                continue;
            }

            $cartAddonData[] = $addon;
        }

        $this->view('cart/index', [
            'items' => $cartAddonData,
            'cart_items_count' => Cart::where('active', 1)->count()
        ]);
    }
}
