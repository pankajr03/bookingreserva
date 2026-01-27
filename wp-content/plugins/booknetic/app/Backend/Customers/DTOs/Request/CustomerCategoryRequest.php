<?php

namespace BookneticApp\Backend\Customers\DTOs\Request;

use BookneticApp\Backend\Customers\Exceptions\InvalidCustomerCategoryDataException;
use BookneticApp\Backend\Customers\Exceptions\InvalidCustomerDataException;

class CustomerCategoryRequest
{
    private string $name;
    private string $color;
    private string $icon;
    private bool $isDefault = false;
    private ?string $note;
    private bool $applyToUncategorizedCustomers;

    public function setNote(?string $note): CustomerCategoryRequest
    {
        $this->note = $note;

        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function getApplyToUncategorizedCustomers(): bool
    {
        return $this->applyToUncategorizedCustomers;
    }

    public function setApplyToUncategorizedCustomers(bool $applyToUncategorizedCustomers): CustomerCategoryRequest
    {
        $this->applyToUncategorizedCustomers = $applyToUncategorizedCustomers;

        return $this;
    }

    /**
     * @param string $name
     * @return CustomerCategoryRequest
     * @return InvalidCustomerCategoryDataException
     * @throws InvalidCustomerCategoryDataException
     */
    public function setName(string $name): CustomerCategoryRequest
    {
        if (empty($name)) {
            throw new InvalidCustomerCategoryDataException(bkntc__('The name field is required!'));
        }

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

    /**
     * @param string $color
     * @return CustomerRequest
     * @throws InvalidCustomerDataException
     */
    public function setColor(string $color): CustomerCategoryRequest
    {
        if (empty($color)) {
            throw new InvalidCustomerDataException(bkntc__('The color field is required!'));
        }

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
     * @param string $icon
     * @return CustomerCategoryRequest
     */
    public function setIcon(string $icon): CustomerCategoryRequest
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * @return string
     */
    public function getIcon(): string
    {
        return $this->icon;
    }

    public function setIsDefault(bool $isDefault): CustomerCategoryRequest
    {
        $this->isDefault = $isDefault;

        return $this;
    }

    /**
     * @return bool
     */
    public function isDefault(): bool
    {
        return $this->isDefault;
    }
}
