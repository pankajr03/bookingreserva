<?php

namespace BookneticApp\Backend\Appearance\Mappers;

use BookneticApp\Backend\Appearance\DTOs\Response\AppearanceResponse;
use BookneticApp\Backend\Appearance\Helpers\Theme;
use BookneticApp\Models\Appearance;
use BookneticApp\Providers\DB\Collection;

class AppearanceMapper
{
    /**
     * @param Appearance|Collection $appearance
     *
     * @return AppearanceResponse
     */
    public static function toResponse(Collection $appearance): AppearanceResponse
    {
        $dto = new AppearanceResponse();

        $dto->setId($appearance->id);
        $dto->setName($appearance->name);
        $dto->setCustomCss($appearance['custom_css'] ?? '');
        $dto->setHeight($appearance['height'] ?? '');
        $dto->setFontFamily($appearance['fontfamily'] ?? '');
        $dto->setColors(json_decode($appearance['colors'] ?? '[]', true));
        $dto->setHideSteps($appearance['hide_steps'] ?? '');
        $dto->setCssFile($appearance['cssFile'] ?? Theme::getThemeCss($appearance['id']) ?? '');
        $dto->setIsDefault($appearance['is_default'] ?? false);
        $dto->setUseLocalFont($appearance['use_local_font'] ?? false);

        return $dto;
    }
}
