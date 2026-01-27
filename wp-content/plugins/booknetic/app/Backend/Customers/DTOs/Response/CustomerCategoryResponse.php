<?php

namespace BookneticApp\Backend\Customers\DTOs\Response;

class CustomerCategoryResponse
{
    private int $id;
    private string $name;
    private string $icon;
    private string $color;
    private ?string $note;

    private bool $isDefault;

    /**
     * @return CustomerCategoryResponse
     */
    public static function createEmpty(): CustomerCategoryResponse
    {
        $instance = new self();

        $instance->setId(0);
        $instance->setName('');
        $instance->setIcon('');
        $instance->setColor('');
        $instance->setNote('');
        $instance->setIsDefault(false);

        return $instance;
    }

    public function setNote(?string $note): CustomerCategoryResponse
    {
        $this->note = $note;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getNote(): ?string
    {
        return $this->note;
    }

    /**
     * @param int $id
     * @return CustomerCategoryResponse
     */
    public function setId(int $id): CustomerCategoryResponse
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param string $firstName
     * @return CustomerCategoryResponse
     */
    public function setName(string $name): CustomerCategoryResponse
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    public function setColor(string $color): CustomerCategoryResponse
    {
        $this->color = $color;

        return $this;
    }

    /**
     * @return string
     */
    public function getColor(): string
    {
        return $this->color;
    }

    /**
     * @param string|null $phoneNumber
     * @return CustomerCategoryResponse
     */
    public function setIcon(?string $icon): CustomerCategoryResponse
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getIcon(): ?string
    {
        return $this->icon;
    }

    /**
     * @param bool $isDefault
     * @return CustomerCategoryResponse
     */
    public function setIsDefault(bool $isDefault): CustomerCategoryResponse
    {
        $this->isDefault = $isDefault;

        return $this;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }
}
