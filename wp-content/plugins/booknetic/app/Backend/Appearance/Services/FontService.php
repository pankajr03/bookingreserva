<?php

namespace BookneticApp\Backend\Appearance\Services;

use BookneticApp\Providers\Fonts\GoogleFontsImp;

class FontService
{
    public function checkFontStatus(string $fontName): int
    {
        return GoogleFontsImp::getFontStatus($fontName);
    }

    public function downloadFont(string $fontName): bool
    {
        $font = new GoogleFontsImp($fontName);
        $font->downloadFontInBackground();

        return true;
    }

    public function getFontsList(): array
    {
        $json_file = __DIR__ . '/../assets/fonts.json';

        if (file_exists($json_file)) {
            $json = file_get_contents($json_file);
            $fonts = json_decode($json, true);
        } else {
            $fonts = [];
        }

        return $fonts;
    }
}
