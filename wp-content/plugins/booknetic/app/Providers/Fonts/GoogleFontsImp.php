<?php

namespace BookneticApp\Providers\Fonts;

use BookneticApp\Providers\Helpers\Helper;
use BookneticVendor\GuzzleHttp\Client as GuzzleHttpClient;
use BookneticVendor\GuzzleHttp\Exception\GuzzleException;
use BookneticVendor\GuzzleHttp\Exception\RequestException as ExceptionRequestException;

/**
 * Google Fonts Implementation
 */
class GoogleFontsImp
{
    protected string $fontName;
    public static string $moduleName = 'Fonts';
    protected array $subsets = [];
    protected string $display;
    protected array $fontVariants;

    private array $unicodeRanges = [];

    public function __construct(string $fontName, array $fontVariants = ['100', '100i', '200', '200i', '300', '300i', '400', '400i', '500', '500i', '600', '600i', '700', '700i', '800', '800i', '900', '900i'], string $display = 'swap')
    {
        $fontVariants = array_map('sanitize_text_field', (array)$fontVariants);
        sort($fontVariants);
        $fontWeights = [];
        foreach ($fontVariants as $variant) {
            $isItalic = str_contains($variant, 'i');
            $weight = $isItalic ? rtrim($variant, 'i') : $variant;
            $fontWeights[$weight] = $fontWeights[$weight] ?? ['regular' => false, 'italic' => false];
            $fontWeights[$weight][$isItalic ? 'italic' : 'regular'] = true;
        }
        $this->fontName = str_replace(' ', '+', sanitize_text_field($fontName));
        $this->fontVariants = $fontWeights;
        $this->display = in_array($display, ['auto', 'block', 'swap', 'fallback', 'optional']) ? $display : 'swap';
    }

    /**
     * Check if font exists in uploads directory
     * @return bool
     */
    protected function fontExists(): bool
    {
        $this->subsets = [];

        $files = $this->getAllFontFiles();
        foreach ($this->fontVariants as $weight => $styles) {
            foreach (['regular', 'italic'] as $style) {
                if (empty($styles[$style])) {
                    continue;
                }
                $regex = '/^' . preg_quote(strtolower($this->fontName), '/') . '-' . $weight . '-' . $style . '-(.*)\.woff2$/';

                $matchFound = false;

                foreach ($files as $file) {
                    if (!preg_match($regex, $file, $matches)) {
                        continue;
                    }
                    $matchFound = true;

                    if (isset($matches[1])) {
                        $this->subsets[] = $matches[1];
                    }
                }
            }
        }

        $this->subsets = array_unique($this->subsets);
        $regex = '/^' . preg_quote(strtolower($this->fontName), '/') . '\.css$/';
        $matchFound = false;

        foreach ($files as $file) {
            if (preg_match($regex, $file, $matches)) {
                $matchFound = true;
            }
        }

        return $matchFound;
    }

    /**
     * Generate google fonts url for currect font
     * @return string
     */
    public function generateGoogleFontsUrl(): string
    {
        $fontNameFormatted = str_replace(' ', '+', $this->fontName);
        $variants = [];

        $regularWeights = [];
        $italicWeights = [];
        foreach ($this->fontVariants as $weight => $styles) {
            $weight = preg_replace('/\D/', '', $weight);
            if ($styles['regular']) {
                $regularWeights[] = "0,{$weight}";
            }
            if ($styles['italic']) {
                $italicWeights[] = "1,{$weight}";
            }
        }
        $variants = array_merge($regularWeights, $italicWeights);
        $variantsStr = implode(';', $variants);

        return "https://fonts.googleapis.com/css2?family={$fontNameFormatted}:ital,wght@{$variantsStr}&display={$this->display}";
    }

    /**
     * Download font from Google Fonts
     * @return bool
     * @throws GuzzleException
     */
    protected function downloadFont(): bool
    {
        $fontFiles = [];

        $googleFontUrl = $this->generateGoogleFontsUrl();

        $client = new GuzzleHttpClient([
            'timeout' => 35,
            'headers' => [
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
                'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36',
            ]
        ]);

        $cssContent = null;

        try {
            $response = $client->request('GET', $googleFontUrl);
            if ($response->getStatusCode() !== 200) {
                return false;
            }

            $cssContent = $response->getBody()->getContents();
        } catch (ExceptionRequestException $e) {
            return false;
        }

        if (empty($cssContent)) {
            return false;
        }

        preg_match_all('/\/\*\s*(.*?)\s*\*\/\s*@font-face\s*{([^}]*)}/s', $cssContent, $fontFaceMatches, PREG_SET_ORDER);
        if (empty($fontFaceMatches[1])) {
            return false;
        }

        $font_rules = [];
        foreach ($fontFaceMatches as $match) {
            $fontSubset = trim($match[1]);
            $rule = $match[2];

            $fontWeight = '';
            $fontStyle = '';
            $fontUrl = '';
            $unicodeRange = '';

            if (preg_match('/font-weight:\s*(\d+)/', $rule, $weightMatch)) {
                $fontWeight = $weightMatch[1];
            }

            if (preg_match('/font-style:\s*(\w+)/', $rule, $styleMatch)) {
                $fontStyle = $styleMatch[1];
            }

            if (preg_match('/url\((.*?)\)/', $rule, $urlMatch)) {
                $fontUrl = $urlMatch[1];
            }

            if (preg_match('/unicode-range:\s*([^;]+)/', $rule, $rangeMatch)) {
                $unicodeRange = trim($rangeMatch[1]);
            }

            if ($fontWeight && $fontStyle && $fontUrl) {
                $font_rules[] = [
                    'weight' => $fontWeight,
                    'style' => $fontStyle,
                    'url' => $fontUrl,
                    'unicodeRange' => $unicodeRange,
                    'subset' => $fontSubset
                ];
                $this->unicodeRanges[$fontSubset] = $unicodeRange;
                $this->subsets[] = $fontSubset;
            }
        }

        foreach ($this->subsets as $subset) {
            foreach ($this->fontVariants as $weight => $styles) {
                foreach (['regular' => 'normal', 'italic' => 'italic'] as $styleKey => $styleValue) {
                    if (empty($styles[$styleKey])) {
                        continue;
                    }
                    $weight = preg_replace('/\D/', '', $weight);

                    $fontFileUrl = null;
                    foreach ($font_rules as $rule) {
                        if (
                            $rule['weight'] === $weight &&
                            $rule['style'] === $styleValue &&
                            strtolower($rule['subset']) === strtolower($subset)
                        ) {
                            $fontFileUrl = $rule['url'];
                            break;
                        }
                    }

                    if (!$fontFileUrl) {
                        continue;
                    }

                    try {
                        $fontResponse = $client->request('GET', $fontFileUrl);

                        if ($fontResponse->getStatusCode() !== 200) {
                            continue;
                        }

                        $fontContent = $fontResponse->getBody()->getContents();

                        $fontFilename = strtolower($this->fontName) . '-' . $weight . '-' . $styleKey . '-' . $subset . '.woff2';
                        $fontPath = Helper::uploadedFile($fontFilename, self::$moduleName);
                        if (file_put_contents($fontPath, $fontContent)) {
                            $fontFiles[$subset . '-' . $weight . '-' . $styleKey] = Helper::uploadedFileURL($fontFilename, self::$moduleName);
                        }
                    } catch (ExceptionRequestException $e) {
                        continue;
                    }
                }
            }
        }

        return !empty($fontFiles);
    }

    /**
     * Generate font URL with variant and subset support
     * @param array $fontFiles Array of font files with weights, styles, and subsets
     * @return string
     */
    public function generateFontUrl(array $fontFiles): string
    {
        if (empty($fontFiles)) {
            return '';
        }

        $styleCombos = [];
        $usedSubsets = [];

        foreach ($fontFiles as $key => $url) {
            [$subset, $weight, $style] = explode('-', $key);

            $styleCombos["{$weight}{$style}"] = true;
            $usedSubsets[$subset] = true;
        }

        ksort($styleCombos);
        ksort($usedSubsets);

        $comboStr = implode('-', array_keys($styleCombos));

        $cssFilename = strtolower($this->fontName) . ".css";
        $cssPath = Helper::uploadedFile($cssFilename, self::$moduleName);
        $cssUrl =  Helper::uploadedFileURL($cssFilename, self::$moduleName);

        if (file_exists($cssPath)) {
            return $cssUrl;
        }

        $css = '';
        $fontName = str_replace('+', ' ', $this->fontName);
        foreach ($this->subsets as $subset) {
            foreach ($this->fontVariants as $weight => $styles) {
                foreach (['regular' => false, 'italic' => true] as $style => $isItalic) {
                    if (empty($styles[$style])) {
                        continue;
                    }

                    $key = "{$subset}-{$weight}-{$style}";
                    if (!isset($fontFiles[$key])) {
                        continue;
                    }
                    $css .= "/* {$subset} */\n";
                    $css .= "@font-face {\n";
                    $css .= "    font-family: '{$fontName}';\n";
                    $css .= "    src: local('{$fontName}'),\n";
                    $css .= "        url('{$fontFiles[$key]}') format('woff2');\n";
                    $css .= "    font-weight: {$weight};\n";
                    $css .= "    font-style: " . ($isItalic ? 'italic' : 'normal') . ";\n";
                    $css .= "    font-display: {$this->display};\n";
                    $css .= "    unicode-range: " . $this->getUnicodeRange($subset) . ";\n";
                    $css .= "}\n";
                }
            }
        }

        if (empty($css)) {
            return '';
        }

        file_put_contents($cssPath, $css);

        return $cssUrl;
    }

    /**
     * Get font files with variant and subset support
     * @return array
     */
    protected function getFontFiles(): array
    {
        $fontFiles = [];
        foreach ($this->subsets as $subset) {
            foreach ($this->fontVariants as $weight => $styles) {
                foreach (['regular', 'italic'] as $style) {
                    if (empty($styles[$style])) {
                        continue;
                    }

                    $weight = preg_replace('/\D/', '', $weight);
                    $fileName = strtolower($this->fontName) . '-' . $weight . '-' . $style . '-' . $subset . '.woff2';
                    $fontPath = Helper::uploadedFile($fileName, self::$moduleName);
                    if (file_exists($fontPath)) {
                        $fontFiles[$subset . '-' . $weight . '-' . $style] = Helper::uploadedFileURL($fileName, self::$moduleName);
                    }
                }
            }
        }

        return $fontFiles;
    }

    /**
     * Get unicode range for subset
     * @param string $subset
     * @return string
     */
    protected function getUnicodeRange(string $subset): string
    {
        $latin = 'U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD';

        return $this->unicodeRanges[$subset] ?? $latin;
    }

    public function getUrl(): string
    {
        if ($this->fontExists()) {
            return $this->generateFontUrl($this->getFontFiles());
        }

        return $this->generateGoogleFontsUrl();
    }

    public function downloadFontInBackground(): void
    {
        $this->downloadFont();
        $this->generateFontUrl($this->getFontFiles());
    }

    public static function getAllFontFiles(): array
    {
        $files = scandir(Helper::uploadFolder(self::$moduleName));
        if (!$files) {
            return [];
        }

        return $files;
    }

    public static function getFontStatus(string $fontName): int
    {
        $fontName = strtolower($fontName);
        $fontName = str_replace(" ", "+", $fontName);
        if (file_exists(Helper::uploadedFile($fontName . '.css', self::$moduleName))) {
            return 2;
        }
        $regex = '/^' . preg_quote($fontName, '/') . '-' . '.*$/i';

        $files = self::getAllFontFiles();
        foreach ($files as $file) {
            if (preg_match($regex, $file)) {
                return 1;
            }
        }

        return 0;
    }
}
