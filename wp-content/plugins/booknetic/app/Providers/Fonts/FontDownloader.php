<?php

namespace BookneticApp\Providers\Fonts;

use BookneticVendor\WP_Async_Request;

class FontDownloader extends WP_Async_Request
{
    /**
     * @var string
     */
    protected $action = 'bkntc_font_download';

    protected array $variants = ['100', '100i', '200', '200i', '300', '300i', '400', '400i', '500', '500i', '600', '600i', '700', '700i', '800', '800i', '900', '900i'];

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Handle
     *
     * Override this method to perform any actions required
     * during the async request.
     */
    protected function handle(): void
    {
        $fontName = sanitize_text_field($_POST['name']);

        $f = new GoogleFontsImp($fontName, $this->variants);
        $f->downloadFontInBackground();
    }

    public static function register(): void
    {
        new self();
    }
}
