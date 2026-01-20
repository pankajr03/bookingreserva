<?php

namespace BookneticApp\Backend\Customers\DTOs\Response;

use BookneticApp\Backend\Base\DTOs\Response\SelectOptionResponse;

class CustomerViewResponse
{
    private CustomerResponse $customer;
    private bool $isEmailRequired;
    private bool $isPhoneRequired;

    /**
     * @var array<SelectOptionResponse>
     */
    private array $users;
    private bool $hasWpUser;
    private bool $isFullNameEnabled;

    private array $categories;

    /**
     * @param CustomerResponse $customer
     * @return array
     */
    public function getCategories(): array
    {
        return $this->categories;
    }

    /**
     * @param array $categories
     * @return CustomerViewResponse
     */
    public function setCategories(array $categories): CustomerViewResponse
    {
        $this->categories = $categories;

        return $this;
    }

    /**
     * @return CustomerResponse
     */
    public function getCustomer(): CustomerResponse
    {
        return $this->customer;
    }

    /**
     * @param CustomerResponse $customer
     * @return void
     */
    public function setCustomer(CustomerResponse $customer): void
    {
        $this->customer = $customer;
    }

    /**
     * @return bool
     */
    public function isEmailRequired(): bool
    {
        return $this->isEmailRequired;
    }

    /**
     * @param bool $isEmailRequired
     */
    public function setIsEmailRequired(bool $isEmailRequired): void
    {
        $this->isEmailRequired = $isEmailRequired;
    }

    /**
     * @return bool
     */
    public function isPhoneRequired(): bool
    {
        return $this->isPhoneRequired;
    }

    /**
     * @param bool $isPhoneRequired
     */
    public function setIsPhoneRequired(bool $isPhoneRequired): void
    {
        $this->isPhoneRequired = $isPhoneRequired;
    }

    /**
     * @return array<SelectOptionResponse>
     */
    public function getUsers(): array
    {
        return $this->users;
    }

    /**
     * @param array<SelectOptionResponse> $users
     * @return void
     */
    public function setUsers(array $users): void
    {
        $this->users = $users;
    }

    /**
     * @return bool
     */
    public function hasWpUser(): bool
    {
        return $this->hasWpUser;
    }

    public function setHasWpUser(bool $hasWpUser): void
    {
        $this->hasWpUser = $hasWpUser;
    }

    /**
     * @return bool
     */
    public function isFullNameEnabled(): bool
    {
        return $this->isFullNameEnabled;
    }

    /**
     * @param bool $isFullNameEnabled
     */
    public function setIsFullNameEnabled(bool $isFullNameEnabled): void
    {
        $this->isFullNameEnabled = $isFullNameEnabled;
    }
}
