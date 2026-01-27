<?php

namespace BookneticApp\Backend\Appearance\DTOs\Response;

class AppearanceResponse
{
    private int $id;
    private string $name;
    private string $customCss;
    private string $height;
    private string $fontFamily;
    private array $colors;
    private string $hideSteps;

    private string $cssFile;

    private bool $isDefault;

    private bool $useLocalFont;

    public static function createEmpty(): AppearanceResponse
    {
        $instance = new self();

        $instance->setId(0);
        $instance->setName('');
        $instance->setCustomCss('');
        $instance->setHeight('');
        $instance->setFontFamily('');
        $instance->setColors([]);
        $instance->setHideSteps('');
        $instance->setCssFile('');
        $instance->setIsDefault(false);
        $instance->setUseLocalFont(false);

        return $instance;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): AppearanceResponse
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): AppearanceResponse
    {
        $this->name = $name;

        return $this;
    }

    public function getCustomCss(): string
    {
        return $this->customCss;
    }

    public function setCustomCss(string $customCss): AppearanceResponse
    {
        $this->customCss = $customCss;

        return $this;
    }

    public function getHeight(): string
    {
        return $this->height;
    }

    public function setHeight(string $height): AppearanceResponse
    {
        $this->height = $height;

        return $this;
    }

    public function getFontFamily(): string
    {
        return $this->fontFamily;
    }

    public function setFontFamily(string $fontFamily): AppearanceResponse
    {
        $this->fontFamily = $fontFamily;

        return $this;
    }

    public function getColors(): array
    {
        return $this->colors;
    }

    public function setColors(array $colors): AppearanceResponse
    {
        $this->colors = $colors;

        return $this;
    }

    public function getHideSteps(): string
    {
        return $this->hideSteps;
    }

    public function setHideSteps(string $hideSteps): AppearanceResponse
    {
        $this->hideSteps = $hideSteps;

        return $this;
    }

    public function getCssFile(): string
    {
        return $this->cssFile;
    }

    public function setCssFile(string $cssFile): AppearanceResponse
    {
        $this->cssFile = $cssFile;

        return $this;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    public function setIsDefault(bool $isDefault): AppearanceResponse
    {
        $this->isDefault = $isDefault;

        return $this;
    }

    public function getUseLocalFont(): int
    {
        return $this->useLocalFont;
    }

    public function setUseLocalFont(bool $useLocalFont): AppearanceResponse
    {
        $this->useLocalFont = $useLocalFont;

        return $this;
    }
}
