<?php

namespace BookneticApp\Backend\Services\Mappers;

use BookneticApp\Backend\Services\DTOs\Response\ServiceCategoryResponse;

class ServiceCategoryMapper
{
    public static function toResponse($serviceCategory): ServiceCategoryResponse
    {
        $dto = new ServiceCategoryResponse();

        $dto->setId($serviceCategory->id);
        $dto->setName($serviceCategory->name);
        $dto->setParentId($serviceCategory->parent_id);

        return $dto;
    }
}
