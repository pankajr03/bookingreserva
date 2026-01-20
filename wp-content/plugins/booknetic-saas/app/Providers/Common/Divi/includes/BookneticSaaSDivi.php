<?php

namespace BookneticSaaS\Providers\Common\Divi\includes;

use DiviExtension;

class BookneticSaaSDivi extends DiviExtension
{
    /**
     * BookneticSaaSDivi constructor.
     *
     * @param string $name
     * @param array  $args
     */
    public function __construct($name = 'booknetic-saas', $args = array())
    {
        $this->plugin_dir     = plugin_dir_path(__FILE__);
        $this->plugin_dir_url = plugin_dir_url($this->plugin_dir);
        parent::__construct($name, $args);
    }
}
