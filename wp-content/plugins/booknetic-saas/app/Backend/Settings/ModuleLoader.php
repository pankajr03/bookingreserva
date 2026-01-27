<?php

namespace BookneticSaaS\Backend\Settings;

use BookneticApp\Providers\IoC\Container;

interface ModuleLoader
{
    public static function load(Container $container): void;
}
