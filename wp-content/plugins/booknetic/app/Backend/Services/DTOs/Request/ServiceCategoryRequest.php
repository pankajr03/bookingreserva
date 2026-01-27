<?php

namespace BookneticApp\Backend\Services\DTOs\Request;

use BookneticApp\Backend\Services\Exceptions\NameRequiredException;

class ServiceCategoryRequest
{
    private string $name;
    private int $parentId;

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return $this
     * @throws NameRequiredException
     */
    public function setName(string $name): ServiceCategoryRequest
    {
        if ($name === '') {
            throw new NameRequiredException();
        }

        $this->name = $name;

        return $this;
    }

    /**
     * @return int
     */
    public function getParentId(): int
    {
        return $this->parentId;
    }

    /**
     * @param int $parentId
     * @return $this
     */
    public function setParentId(int $parentId): ServiceCategoryRequest
    {
        $this->parentId = $parentId;

        return $this;
    }
}
