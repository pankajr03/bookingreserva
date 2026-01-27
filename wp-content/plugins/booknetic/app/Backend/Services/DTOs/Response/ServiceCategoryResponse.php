<?php

namespace BookneticApp\Backend\Services\DTOs\Response;

class ServiceCategoryResponse
{
    private int $id;
    private string $name;
    private int $parentId;

    /**
     * @return ServiceCategoryResponse
     */
    public static function createEmpty(): ServiceCategoryResponse
    {
        $instance = new self();

        $instance->setId(0);
        $instance->setName('');
        $instance->setParentId(0);

        return $instance;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return $this
     */
    public function setId(int $id): ServiceCategoryResponse
    {
        $this->id = $id;

        return $this;
    }

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
     */
    public function setName(string $name): ServiceCategoryResponse
    {
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
    public function setParentId(int $parentId): ServiceCategoryResponse
    {
        $this->parentId = $parentId;

        return $this;
    }
}
