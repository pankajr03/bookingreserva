<?php

namespace BookneticSaaS\Providers\Core;

use BookneticApp\Providers\FSCode\Clients\FSCodeAPIClient;
use BookneticApp\Providers\IoC\Container;
use BookneticSaaS\Providers\Helpers\Helper;
use Exception;
use stdClass;

class PluginUpdater
{
    private $expiration = 43200; // seconds
    private $plugin_slug;
    private $plugin_base;
    private $transient; // temporary cache for multiple calls

    public function __construct($plugin)
    {
        $this->plugin_slug   = $plugin;
        $this->plugin_base   = $plugin . '/init.php';
    }

    public function plugin_info($res, $action, $args)
    {
        if ($action !== 'plugin_information') {
            return false;
        }

        if ($args->slug !== $this->plugin_slug) {
            return false;
        }

        $remote = $this->get_transient();

        if ($remote && isset($remote->version)) {
            $res = new stdClass();

            $res->name         = $remote->name;
            $res->slug         = $this->plugin_slug;
            $res->tested       = $remote->tested;
            $res->version      = $remote->version;
            $res->last_updated = $remote->last_updated;

            $res->author         = '<a href="https://www.fs-code.com">FS Code</a>';
            $res->author_profile = 'https://www.fs-code.com';

            $res->sections = [
                'description' => $remote->sections->description,
                'changelog'   => $remote->sections->changelog
            ];

            return $res;
        }

        return false;
    }

    public function push_update($transient)
    {
        if (empty($transient->checked)) {
            return $transient;
        }

        $remote = $this->get_transient();

        if ($remote && isset($remote->version) && version_compare(Helper::getVersion(), $remote->version, '<')) {
            $res = new stdClass();

            $res->slug          = $this->plugin_slug;
            $res->plugin        = $this->plugin_base;
            $res->new_version   = $remote->version;
            $res->tested        = $remote->tested;
            $res->package       = $remote->download_url ?? '';
            $res->update_notice = $remote->update_notice ?? '';
            $res->compatibility = new stdClass();

            $transient->response[ $res->plugin ] = $res;
        }

        return $transient;
    }

    public function after_update($upgrader_object, $options)
    {
        if ($options[ 'action' ] === 'update' && $options[ 'type' ] === 'plugin') {
            delete_transient('fscode_upgrade_' . $this->plugin_slug);
        }
    }

    public function check_for_update($links, $file)
    {
        if (strpos($file, $this->plugin_base) !== false) {
            $new_links = [
                'check_for_update' => '<a href="plugins.php?fscode_check_for_update=1&plugin=' . urlencode($this->plugin_slug) . '&_wpnonce=' . wp_create_nonce('fscode_check_for_update_' . $this->plugin_slug) . '">Check for update</a>'
            ];

            $links = array_merge($links, $new_links);
        }

        return $links;
    }

    public function block_expired_updates($reply, $package, $extra_data)
    {
        if ($reply !== false) {
            return $reply;
        }

        if (property_exists($extra_data->skin, 'plugin_info') && $extra_data->skin->plugin_info[ 'TextDomain' ] !== $this->plugin_slug) {
            return false;
        }

        $remote = $this->get_transient();

        if (! $remote || empty($remote->update_notice)) {
            return false;
        }

        $update_notice = '<div class="fsp-plugin-blocked-notice">' . $remote->update_notice . '</div>';

        return new \WP_Error($this->plugin_slug . '_subscription_expired', $update_notice);
    }

    public function plugin_update_message($plugin_data, $extra_data)
    {
        if (empty($extra_data->package) && ! empty($extra_data->update_notice)) {
            echo '<style>
                            #'.$this->plugin_slug.'-update .update-message em
                            {
                                display: none;
                            }

                            .fsp-plugin-update-notice {
                                font-size: 13px;
                                line-height: 1.5em;
                                margin: .5em 0;
                                color: #32373c;
                                border-top: 2px solid #ffb900;
                                padding-top: .5em;
                                font-weight: 500;
                            }

                            .fsp-plugin-update-notice + p {
                                display: none;
                            }
                            
                            .fsp-plugin-blocked-notice {
                                font-weight: bold;
                                margin-top: -24px;
                                background: inherit;
                                position: relative;
                            }
                        </style>';
            echo '<div class="fsp-plugin-update-notice">' . $extra_data->update_notice . '</div>';
        }
    }

    /*
     * First check if temporary cache is available, if it is, use it
     * Second check long-live cache, and if it is in the expiration timeframe, use it
     * If neither cache is available, then request to remote server and cache it
     */
    private function get_transient()
    {
        if (isset($this->transient)) {
            return $this->transient;
        }

        try {
            $transient_cache = json_decode(Helper::getOption('transient_cache_' . $this->plugin_slug, false, false), false);

            if (empty($transient_cache)) {
                throw new Exception();
            }

            $transient       = $transient_cache->transient;
            $time            = $transient_cache->time;
        } catch (Exception $e) {
            $transient = false;
            $time = 0;
        }

        if (! $transient || time() - $time > $this->expiration) {
            try {
                $apiClient = Container::get(FSCodeAPIClient::class);

                try {
                    $response = $apiClient->requestNew('booknetic-saas/product/check_update', 'POST');
                    $result = $response->getData();
                    $transient = $result['data'] ?? false;
                } catch (Exception $e) {
                    $transient = false;
                }

                // long-live cache
                Helper::setOption('transient_cache_' . $this->plugin_slug, json_encode([
                    'time'      => time(),
                    'transient' => $transient ?: 1
                ]), false);
            } catch (Exception $e) {
                Helper::setOption('transient_cache_' . $this->plugin_slug, json_encode([
                    'time'      => time(),
                    'transient' => 1
                ]), false);
            }
        }

        $this->transient = $transient;

        return $transient;
    }

    /*
     * Set expiration limit to 1 minute if update check is forced
     * There should be at lease 1 minute difference between two requests
     */
    public function check_if_forced_for_update()
    {
        $check_update = Helper::_get('fscode_check_for_update', '', 'string');
        $plugin       = Helper::_get('plugin', '', 'string');
        $_wpnonce     = Helper::_get('_wpnonce', '', 'string');

        if ($check_update === '1' && $plugin === $this->plugin_slug && wp_verify_nonce($_wpnonce, 'fscode_check_for_update_' . $this->plugin_slug)) {
            $this->expiration = 0;
        }
    }

    public function set_filters()
    {
        add_filter('plugins_api', [ $this, 'plugin_info' ], 20, 3);
        add_filter('site_transient_update_plugins', [ $this, 'push_update' ]);
        add_action('upgrader_process_complete', [ $this, 'after_update' ], 10, 2);
        add_filter('plugin_row_meta', [ $this, 'check_for_update' ], 10, 2);
        add_filter('upgrader_pre_download', [ $this, 'block_expired_updates' ], 10, 3);

        add_action('in_plugin_update_message-' . $this->plugin_slug . '/init.php', [
            $this,
            'plugin_update_message'
        ], 10, 2);
    }
}
