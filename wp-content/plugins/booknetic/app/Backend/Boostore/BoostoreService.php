<?php

namespace BookneticApp\Backend\Boostore;

use BookneticApp\Models\Cart;
use RuntimeException;

class BoostoreService
{
    /**
     * @throws RuntimeException
     */
    public function addToCart(string $slug): void
    {
        $cartItem = Cart::where([ 'slug' =>  $slug, 'active' => 1 ])->fetch();

        if ($cartItem !== null) {
            throw new RuntimeException(bkntc__('Addon already exists in your cart'));
        }

        Cart::insert([
            'slug'     => $slug,
            'active'    => 1,
            'created_at' => (new \DateTime())->getTimestamp(),
        ]);
    }
}
