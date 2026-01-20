<?php

namespace BookneticApp\Backend\Customers\Services;

use BookneticApp\Backend\Customers\DTOs\Request\CustomerCategoryRequest;
use BookneticApp\Backend\Customers\Exceptions\CustomerCategoryNotFoundException;
use BookneticApp\Backend\Customers\Mappers\CustomerCategoryMapper;
use BookneticApp\Backend\Customers\DTOs\Response\CustomerCategoryResponse;
use BookneticApp\Backend\Customers\Repositories\CustomerCategoryRepository;

class CustomerCategoryService
{
    private CustomercategoryRepository $repository;

    public function __construct(CustomerCategoryRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @throws CustomerCategoryNotFoundException
     */
    public function get(int $id): CustomerCategoryResponse
    {
        $customer = $this->repository->get($id);

        if ($customer === null) {
            throw new CustomerCategoryNotFoundException();
        }

        return CustomerCategoryMapper::toResponse($customer);
    }

    public function create(CustomerCategoryRequest $request): int
    {
        $data = [
            'name' => $request->getName(),
            'icon' => $request->getIcon(),
            'color' => $request->getColor(),
            'is_default' => $request->isDefault(),
            'note' => $request->getNote(),
        ];

        return $this->repository->create($data, $request->getApplyToUncategorizedCustomers());
    }

    public function update(int $id, CustomerCategoryRequest $request): int
    {
        $data = [
            'name' => $request->getName(),
            'icon' => $request->getIcon(),
            'color' => $request->getColor(),
            'is_default' => $request->isDefault(),
            'note' => $request->getNote(),
        ];

        $this->repository->update($id, $data, $request->getApplyToUncategorizedCustomers());

        return $id;
    }

    public function deleteAll(array $ids): void
    {
        $this->repository->delete($ids);
    }

    public function getUncategorizedCustomerCount(): int
    {
        return $this->repository->getUncategorizedCustomerCount();
    }
}
