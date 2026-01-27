<?php

namespace BookneticApp\Backend\Boostore\Helpers;

use BookneticApp\Models\Cart;
use BookneticApp\Providers\FSCode\Clients\FSCodeAPIClient;
use BookneticApp\Providers\IoC\Container;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Core\Bootstrap;
use BookneticApp\Providers\Core\PluginInstaller;

class BoostoreHelper
{
    public static function get($slug, $data = [], $default = [])
    {
        $apiClient = Container::get(FSCodeAPIClient::class);
        $product = Helper::isSaaSVersion() ? 'booknetic-saas' : 'booknetic';
        $endpoint = trim($product.'/addons/' . $slug, '/');
        try {
            $response = $apiClient->requestNew($endpoint, 'POST', $data);

            $apiRes = $response->getData();

            $status = $apiRes['status'] ?? false;
            if (! $status || ! isset($apiRes['data'])) {
                return $default;
            }

            return $apiRes['data'];
        } catch (\Exception $e) {
            return $default;
        }
    }

    public static function getAddonSlug($slug)
    {
        $plugins = get_plugins();

        foreach (array_keys($plugins) as $pluginKey) {
            if (explode('/', $pluginKey)[0] === $slug) {
                return $pluginKey;
            }
        }

        return '';
    }

    public static function installAddon($slug, $downloadURL): bool
    {
        ignore_user_abort(true);
        set_time_limit(0);

        $addonInstaller = new PluginInstaller($downloadURL, $slug);

        if ($addonInstaller->install()) {
            return activate_plugin(self::getAddonSlug($slug)) === null;
        }

        return false;
    }

    public static function uninstallAddon($slug): bool
    {
        $realSlug = self::getAddonSlug($slug);

        if (empty($realSlug)) {
            return false;
        }

        $realPath = realpath(WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $realSlug);

        if (!file_exists($realPath)) {
            return false;
        }

        unset(Bootstrap::$addons[$slug]);

        return delete_plugins([$realSlug]) === true;
    }

    public static function getAllAddons()
    {
        return self::get('/', ['list_all_addons' => true], ['items' => []]);
    }

    public static function isInstalled($slug): bool
    {
        return !empty(self::getAddonSlug($slug)) &&
            file_exists(
                realpath(
                    WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . self::getAddonSlug($slug)
                )
            );
    }

    public static function recalculatePrices(): array
    {
        $cart = Cart::select('slug')->where('active', 1)->fetchAll();

        if (empty($cart)) {
            return [];
        }

        $cartPrices = [];
        $totalPrice = 0;

        $cart = array_map(static fn ($addon) => $addon->slug, $cart);

        foreach (static::getAllAddons()['items'] as $addon) {
            if (in_array($addon['slug'], $cart, true)) {
                $cartPrices[] = ['slug' => $addon['slug'], 'price' => $addon['price']['current']];
                $totalPrice += $addon['price']['current'];
            }
        }

        $cartPrices['total_price'] = $totalPrice;

        return $cartPrices;
    }

    public static function checkAllAddonsInCart(): bool
    {
        $totalAddons = (int)Helper::getOption('total_addons_count');
        $cartCount = Cart::where('active', 1)->count();

        return $totalAddons === $cartCount;
    }

    public static function checkAllAddonsUnowned(): bool
    {
        $addons = self::getAllAddons();
        $unownedAddons = array_filter($addons['items'], fn ($a) => $a['purchase_status'] === 'unowned');

        return count($unownedAddons) === $addons['total'] - 1;
    }

    public static function applyCoupon(string $cart, string $coupon)
    {
        return self::get('apply_discount', [
            'cart' => $cart,
            'coupon' => $coupon,
        ]);
    }

    public static function addAllToCart(array $addons): void
    {
        $now = (new \DateTime())->getTimestamp();

        foreach ($addons as $a) {
            Cart::insert([
                'slug' => $a['slug'],
                'active' => 1,
                'created_at' => $now
            ]);
        }
    }

    public static function filterAllAddons(array $addons): array
    {
        $filteredAddons = self::getUnownendAddons($addons);

        return self::getUnaddedAddons($filteredAddons);
    }

    public static function getUnownendAddons(array $addons): array
    {
        return array_filter($addons, static fn ($a) => $a['purchase_status'] === 'unowned');
    }

    public static function getUnaddedAddons(array $addons): array
    {
        $cart = Cart::where('active', 1)->fetchAll();
        $slugsOfAddonsInCart = array_map(static fn ($a) => $a['slug'], $cart);

        return array_filter($addons, static fn ($a) => !in_array($a['slug'], $slugsOfAddonsInCart, true));
    }
}
