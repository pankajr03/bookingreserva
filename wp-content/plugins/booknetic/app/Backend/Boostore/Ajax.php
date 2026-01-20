<?php

namespace BookneticApp\Backend\Boostore;

use BookneticApp\Backend\Boostore\Helpers\BoostoreHelper;
use BookneticApp\Models\Cart;
use BookneticApp\Providers\Core\Bootstrap;
use BookneticApp\Providers\Core\Permission;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Request\Post;
use Exception;

class Ajax extends \BookneticApp\Providers\Core\Controller
{
    private BoostoreService $service;

    public function __construct()
    {
        $this->service = new BoostoreService();
    }

    public function get_addons()
    {
        $reqBody = [
            'category_ids' => Post::string('category_ids'),
            'search' => Post::string('search'),
            'order_by' => Post::string('order_by'),
            'order_type' => Post::string('order_type'),
            'page' => Post::int('page'),
        ];

        $data = BoostoreHelper::get('/', $reqBody);

        if (empty($data)) {
            return $this->response(false, 'An error occurred, please try again later');
        }

        Helper::setOption('total_addons_count', $data['total'] - 1); // -1 for email addon

        $cartItems = Cart::query()
            ->select('slug')->where('active', 1)
            ->fetchAll();

        $cartItems = array_column($cartItems, 'slug');

        foreach ($data['items'] as $i => $addon) {
            $filePath = realpath(WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . BoostoreHelper::getAddonSlug($addon['slug']));

            $data['items'][$i]['is_installed'] = !empty(BoostoreHelper::getAddonSlug($addon['slug'])) && file_exists($filePath);
            $data['items'][$i]['in_cart'] = in_array($addon['slug'], $cartItems, true);
        }

        //sort items by purchase_status === 'unowned' first and then by is_new=true
        usort($data['items'], static function ($a, $b) {
            if ($a['purchase_status'] === 'unowned' && $b['purchase_status'] !== 'unowned') {
                return -1;
            }

            if ($a['purchase_status'] !== 'unowned' && $b['purchase_status'] === 'unowned') {
                return 1;
            }

            if ($a['is_new'] && !$b['is_new']) {
                return -1;
            }

            if (!$a['is_new'] && $b['is_new']) {
                return 1;
            }

            return 0;
        });

        return $this->modalView('addons_v2', [
            'data' => $data,
            'is_search' => $reqBody['search'],
        ]);
    }

    public function purchase_cart()
    {
        $cart = json_decode(Helper::_post('cart', '', 'string'), true);
        $coupon = BoostoreHelper::checkAllAddonsInCart()
            ? 'buyallcoupon1520231122'
            : Post::string('coupon');

        if (Permission::isDemoVersion()) {
            return $this->response(false, 'You can\'t purchase add-on on Demo version!');
        }

        if (empty($cart) && !is_array($cart)) {
            return $this->response(false, bkntc__('An error occurred, please try again later'));
        }

        $data = BoostoreHelper::get('generate_cart_purchase_url', [
            'redirect_url' => admin_url('admin.php?page=' . Helper::getBackendSlug() . '&module=boostore&action=purchased'),
            'cart' => $cart,
            'coupon' => $coupon,
        ]);

        if (!empty($data['purchase_url'])) {
            return $this->response(true, ['purchase_url' => $data['purchase_url']]);
        }

        if (!empty($data['error_message'])) {
            return $this->response(false, htmlspecialchars($data['error_message']));
        }

        return $this->response(false, bkntc__('An error occurred, please try again later!'));
    }

    public function apply_discount()
    {
        $cart = Post::string('cart');
        $coupon = Post::string('coupon');

        if (Permission::isDemoVersion()) {
            return $this->response(false, 'You can\'t use coupons on Demo version!');
        }

        if (empty($coupon)) {
            return $this->response(false, bkntc__('Coupon cannot be empty!'));
        }

        if (empty($cart)) {
            return $this->response(false, bkntc__('An error occurred, please try again later'));
        }

        $data = BoostoreHelper::applyCoupon($cart, $coupon);

        if (!empty($data['discounted_addons']) && !empty($data['total_price'])) {
            return $this->response(true, ['discounted_addons' => $data['discounted_addons'], 'total_price' => $data['total_price']]);
        }

        if (!empty($data['error_message'])) {
            return $this->response(false, htmlspecialchars($data['error_message']));
        }

        return $this->response(false, bkntc__('An error occurred, please try again later!'));
    }

    public function add_to_cart()
    {
        $slug = Post::string('addon');

        if (Permission::isDemoVersion()) {
            return $this->response(false, 'You can\'t purchase add-on on Demo version!');
        }

        if (empty($slug)) {
            return $this->response(false, bkntc__('An error occurred, please try again later'));
        }

        try {
            $this->service->addToCart($slug);
        } catch (Exception $e) {
            return $this->response(false, htmlspecialchars($e->getMessage()));
        }

        return $this->response(true, ['message' => bkntc__('Added to cart')]);
    }

    public function remove_from_cart()
    {
        $addonSlug = Post::string('addon');

        if (Permission::isDemoVersion()) {
            return $this->response(false, 'You can\'t purchase add-on on Demo version!');
        }

        if (empty($addonSlug)) {
            return $this->response(false, bkntc__('An error occurred, please try again later'));
        }

        Cart::where(['slug' => $addonSlug, 'active' => 1])->update([
            'active' => 0,
            'removed_at' => (new \DateTime())->getTimestamp(),
        ]);

        return $this->response(true, [
            'message' => bkntc__('Removed from cart.'),
            'prices' => BoostoreHelper::recalculatePrices(),
        ]);
    }

    public function install()
    {
        $addonSlug = Post::string('addon_slug');

        if (Permission::isDemoVersion()) {
            return $this->response(false, 'You can\'t install add-on on Demo version!');
        }

        if (empty($addonSlug)) {
            return $this->response(false, bkntc__('An error occurred, please try again later'));
        }

        $data = BoostoreHelper::get('generate_download_url/' . $addonSlug);

        if (!empty($data['download_url']) && BoostoreHelper::installAddon($addonSlug, $data['download_url'])) {
            return $this->response(true, ['message' => bkntc__('Installed successfully!')]);
        }

        if (!empty($data['error_message'])) {
            return $this->response(false, htmlspecialchars($data['error_message']));
        }

        return $this->response(false, bkntc__('An error occurred, please try again later!'));
    }

    public function install_finished()
    {
        if (Permission::isDemoVersion()) {
            return $this->response(false);
        }

        Helper::deleteOption('migration_v3', false);

        return $this->response(true);
    }

    public function uninstall()
    {
        $addon = Post::string('addon');

        if (Permission::isDemoVersion()) {
            return $this->response(false, 'You can\'t uninstall add-on on Demo version!');
        }

        if (empty($addon)) {
            return $this->response(false, bkntc__('Addon not found!'));
        }

        if (BoostoreHelper::uninstallAddon($addon)) {
            return $this->response(true, ['message' => bkntc__('Addon uninstalled successfully!')]);
        }

        return $this->response(false, bkntc__('Addon couldn\'t be uninstalled!'));
    }

    public function clear_cart()
    {
        if (Permission::isDemoVersion()) {
            return $this->response(false);
        }

        $cartItems = Cart::query()->select('slug')->where('active', 1)->fetchAll();

        if (empty($cartItems)) {
            return $this->response(false, bkntc__('Your cart is already empty.'));
        }

        Cart::query()
            ->where('active', 1)
            ->update([
                'active' => 0
            ]);

        return $this->response(true, ['message' => bkntc__('Cart cleared!')]);
    }

    public function buy_all()
    {
        if (BoostoreHelper::checkAllAddonsInCart()) {
            return $this->response(false);
        }

        BoostoreHelper::addAllToCart(BoostoreHelper::filterAllAddons(BoostoreHelper::getAllAddons()['items']));

        return $this->response(true, ['message' => bkntc__('All addons added to cart!')]);
    }

    public function apply_buy_all_discount()
    {
        $data = BoostoreHelper::applyCoupon(Post::string('cart'), 'buyallcoupon1520231122');

        if (!empty($data['discounted_addons']) && !empty($data['total_price'])) {
            return $this->response(true, ['discounted_addons' => $data['discounted_addons'], 'total_price' => $data['total_price']]);
        }

        if (!empty($data['error_message'])) {
            return $this->response(false, htmlspecialchars($data['error_message']));
        }

        return $this->response(false, bkntc__('An error occurred, please try again later!'));
    }

    public function tour_guide_setup_done()
    {
        $addon = Post::string('addon');

        Helper::setOption("booknetic-{$addon}_tour_guide_passed", true);

        return $this->response(true);
    }

    public function purchase_crack_addons()
    {
        $addons = Helper::getOption('synced_addons', [], false);

        if (!is_array($addons) || empty($addons)) {
            return $this->response(false, bkntc__('No addons found!'));
        }

        foreach ($addons as $slug => $addon) {
            if (!Bootstrap::isAddonEnabled($slug)) {
                continue;
            }

            if (!is_array($addon)) {
                continue;
            }

            try {
                $this->service->addToCart($slug);
            } catch (Exception $e) {
                continue;
            }
        }

        return $this->response(true);
    }
}
