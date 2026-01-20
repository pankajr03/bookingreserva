<?php

namespace BookneticApp\Backend\Services\DTOs\Response;

class ServiceCategoryViewResponse
{
    private ServiceCategoryResponse $serviceCategory;
    private array $categories = [];

    /**
     * @return ServiceCategoryResponse
     */
    public function getServiceCategory(): ServiceCategoryResponse
    {
        return $this->serviceCategory;
    }

    /**
     * @param ServiceCategoryResponse $serviceCategory
     * @return void
     */
    public function setServiceCategory(ServiceCategoryResponse $serviceCategory): void
    {
        $this->serviceCategory = $serviceCategory;
    }

    /**
     * @return ParentCategoryResponse[]
     */
    public function getCategories(): array
    {
        return $this->categories;
    }

    /**
     * @param array $categories
     * @return void
     */
    public function setCategories(array $categories): void
    {
        foreach ($categories as $category) {
            $parent = new ParentCategoryResponse();

            $parent->setId($category->id);
            $parent->setName($category->name);

            $this->categories[] = $parent;
        }
    }
}
