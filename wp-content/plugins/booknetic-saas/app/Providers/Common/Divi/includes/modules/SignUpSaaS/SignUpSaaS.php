<?php

class SignUpSaaS extends ET_Builder_Module
{
    public $slug       = 'booknetic_saas_signup';
    public $vb_support = 'on';
    private $data;

    protected $module_credits = array(
        'module_uri' => '',
        'author'     => '',
        'author_uri' => '',
    );

    public function init()
    {
        $this->name = bkntc__('Booknetic Sign Up SaaS');

        $this->data['divi_booknetic_saas_options'] = [ 'url' => urlencode(site_url()) ];
    }

    public function get_fields()
    {
        return array(
            'bookneticSaaSDivi' => [
                'label'           => 'Booknetic SaaS Divi Options',
                'type'            => 'hidden',
                'options'         => $this->data['divi_booknetic_saas_options'],
                'toggle_slug'     => 'main_content',
                'default'         => json_encode($this->data['divi_booknetic_saas_options']),
            ],
        );
    }

    public function render($attrs, $content = null, $render_slug)
    {
        return do_shortcode("[booknetic-saas-signup]");
    }
}

new SignUpSaaS();
