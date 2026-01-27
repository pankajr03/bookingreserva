<?php

namespace BookneticApp\Backend\Appearance\Mappers;

use BookneticApp\Backend\Appearance\DTOs\Response\AppearanceResponse;

class AppearanceEditMapper
{
    /**
     * @param array $data
     *
     * @return AppearanceResponse
     */
    public static function toResponse(array $data): AppearanceResponse
    {
        $dto = AppearanceResponse::createEmpty();
        $dto->setColors($data['colors']);
        $dto->setId($data['id']);
        $dto->setName($data['info']['name']);
        $dto->setFontFamily($data['info']['fontfamily']);
        $dto->setHeight($data['info']['height']);
        $dto->setHideSteps($data['info']['hide_steps']);
        $dto->setUseLocalFont((bool) $data['info']['use_local_font']);
        $dto->setCssFile($data['css_file']);

        if (isset($data['info']['custom_css'])) {
            $dto->setCustomCss($data['info']['custom_css']);
        }

        return $dto;
    }
}
