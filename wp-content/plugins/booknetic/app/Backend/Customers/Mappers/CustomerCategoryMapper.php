<?php

namespace BookneticApp\Backend\Customers\Mappers;

use BookneticApp\Backend\Customers\DTOs\Response\CustomerCategoryResponse;
use BookneticApp\Models\CustomerCategory;
use BookneticApp\Providers\DB\Collection;

class CustomerCategoryMapper
{
    /**
     * @param Collection|CustomerCategory $customerCategory
     * @return CustomercategoryResponse
     */
    public static function toResponse(Collection $customerCategory): CustomercategoryResponse
    {
        $dto = new CustomerCategoryResponse();

        return $dto->setId($customerCategory->id)
           ->setName($customerCategory->name)
           ->setColor($customerCategory->color)
           ->setIcon($customerCategory->icon)
           ->setNote($customerCategory->note)
           ->setIsDefault((bool)$customerCategory->is_default);
    }
}
