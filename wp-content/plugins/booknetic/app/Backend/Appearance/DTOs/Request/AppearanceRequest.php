<?php

namespace BookneticApp\Backend\Appearance\DTOs\Request;

use BookneticApp\Backend\Appearance\Exceptions\InvalidFontNameException;
use BookneticApp\Backend\Appearance\Exceptions\InvalidHeightException;
use BookneticApp\Backend\Appearance\Exceptions\RequiredFieldMissingException;

class AppearanceRequest
{
    private string $name;
    private string $customCss;
    private string $height;
    private string $fontFamily;
    private string $colors;
    private string $hideSteps;
    private int $useLocalFont;

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @throws RequiredFieldMissingException
     */
    public function setName(string $name): AppearanceRequest
    {
        if (empty($name)) {
            throw new RequiredFieldMissingException();
        }

        $this->name = $name;

        return $this;
    }

    public function getCustomCss(): string
    {
        return $this->customCss;
    }

    public function setCustomCss(string $customCss): AppearanceRequest
    {
        $this->customCss = $customCss;

        return $this;
    }

    public function getHeight(): string
    {
        return $this->height;
    }

    /**
     * @throws RequiredFieldMissingException
     * @throws InvalidHeightException
     */
    public function setHeight(string $height): AppearanceRequest
    {
        if (empty($height)) {
            throw new RequiredFieldMissingException();
        }

        if ($height < 400 || $height > 1500) {
            throw new InvalidHeightException();
        }

        $this->height = $height;

        return $this;
    }

    public function getFontFamily(): string
    {
        return $this->fontFamily;
    }

    /**
     * @throws RequiredFieldMissingException
     * @throws InvalidFontNameException
     */
    public function setFontFamily(string $fontFamily): AppearanceRequest
    {
        if (empty($fontFamily)) {
            throw new RequiredFieldMissingException();
        }

        if (!preg_match('/^[a-zA-Z0-9\-_. +]+$/', $fontFamily)) {
            throw new InvalidFontNameException();
        }

        $this->fontFamily = $fontFamily;

        return $this;
    }

    public function getColors(): string
    {
        return $this->colors;
    }

    public function setColors(string $colors): AppearanceRequest
    {
        $this->colors = $colors;

        return $this;
    }

    public function getHideSteps(): string
    {
        return $this->hideSteps;
    }

    public function setHideSteps(string $hideSteps): AppearanceRequest
    {
        $this->hideSteps = $hideSteps;

        return $this;
    }

    public function getUseLocalFont(): int
    {
        return $this->useLocalFont;
    }

    public function setUseLocalFont(int $useLocalFont): AppearanceRequest
    {
        $this->useLocalFont = $useLocalFont;

        return $this;
    }
}
