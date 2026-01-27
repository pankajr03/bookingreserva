<?php

namespace BookneticSaaS\Providers\Common\Elementor;

if (!defined('ABSPATH')) {
    exit;
}

class BookneticSaaSElementor
{
    private static $widgets_dir;
    private static $widgets_namespace = '\\BookneticSaaS\\Providers\\Common\\Elementor\\Widgets\\';

    public static function registerWidgets($widgets_manager)
    {
        self::$widgets_dir =  dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Widgets';
        $widgets = glob(self::$widgets_dir . DIRECTORY_SEPARATOR . '*.php');

        foreach ($widgets as $widget) {
            if (file_exists($widget)) {
                $class = str_replace('.php', '', basename($widget));
                $class = is_array($class) ? '' : $class;
                $class = self::$widgets_namespace . $class;

                $widgets_manager->register(new $class());
            }
        }
    }
}
