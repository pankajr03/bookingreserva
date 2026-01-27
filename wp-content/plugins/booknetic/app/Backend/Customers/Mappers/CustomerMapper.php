<?php

namespace BookneticApp\Backend\Customers\Mappers;

use BookneticApp\Backend\Customers\DTOs\Response\CustomerResponse;
use BookneticApp\Models\Customer;
use BookneticApp\Providers\DB\Collection;

class CustomerMapper
{
    /**
     * @param Collection|Customer $customer
     * @return CustomerResponse
     */
    public function toResponse(Collection $customer): CustomerResponse
    {
        $dto = new CustomerResponse();

        $dto->setId($customer->id)
            ->setFirstName($customer->first_name)
            ->setLastName($customer->last_name)
            ->setPhoneNumber($customer->phone_number)
            ->setEmail($customer->email)
            ->setBirthdate($customer->birthdate)
            ->setNotes($customer->notes ?? '')
            ->setProfileImage($customer->profile_image_url)
            ->setUserId($customer->user_id ?? 0)
            ->setCategoryId($customer->category_id ?? 0)
            ->setCategoryName($customer->category_name ?? '')
            ->setGender($customer->gender);

        return $dto;
    }

    /**
     * @param array $customers
     * @return array<CustomerResponse>
     */
    public function toListResponse(array $customers): array
    {
        return array_map([$this, 'toResponse'], $customers);
    }
}
