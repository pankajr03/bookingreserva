<?php

namespace BookneticApp\Backend\Services\Services;

use BookneticApp\Backend\Services\DTOs\Request\ServiceCategoryRequest;
use BookneticApp\Backend\Services\DTOs\Response\ServiceCategoryResponse;
use BookneticApp\Backend\Services\Exceptions\CategoryAlreadyExistException;
use BookneticApp\Backend\Services\Exceptions\HasServiceInThisCategoryException;
use BookneticApp\Backend\Services\Exceptions\NoCategorySelectedException;
use BookneticApp\Backend\Services\Exceptions\RemoveSubCategoryException;
use BookneticApp\Backend\Services\Exceptions\ServiceCategoryNotFoundException;
use BookneticApp\Backend\Services\Exceptions\ServiceCategoryParentCannotBeSelfException;
use BookneticApp\Backend\Services\Mappers\ServiceCategoryMapper;
use BookneticApp\Backend\Services\Repositories\ServiceCategoryRepository;
use BookneticApp\Providers\DB\QueryBuilder;
use BookneticApp\Providers\Helpers\Helper;
use JsonException;

class ServiceCategoryService
{
    private ServiceCategoryRepository $repository;

    public function __construct()
    {
        $this->repository = new ServiceCategoryRepository();
    }

    /**
     * @throws ServiceCategoryNotFoundException
     */
    public function get($id): ServiceCategoryResponse
    {
        $serviceCategory = $this->repository->get($id);

        if ($serviceCategory === null) {
            throw new ServiceCategoryNotFoundException();
        }

        return ServiceCategoryMapper::toResponse($serviceCategory);
    }

    /**
     * @throws CategoryAlreadyExistException
     * @throws JsonException
     */
    public function create(ServiceCategoryRequest $request): int
    {
        $checkIfNameExist = $this->repository->checkIfNameExist($request->getName(), $request->getParentId());

        if ($checkIfNameExist) {
            throw new CategoryAlreadyExistException();
        }

        $data = [
            'name' => $request->getName(),
            'parent_id' => $request->getParentId(),
        ];

        $id = $this->repository->create($data);
        $parent = $request->getParentId();

        $servicesOrder = json_decode(Helper::getOption("services_order", '[]'), true, 512, JSON_THROW_ON_ERROR);
        if (! empty($servicesOrder) && is_array($servicesOrder)) {
            if ($parent === 0) {
                $servicesOrder[ $id ] = [];
            } else {
                $newServiceOrder = [];
                foreach ($servicesOrder as $k => $items) {
                    $newServiceOrder[ $k ] = $items;
                    if ($k === $parent) {
                        $newServiceOrder[ $id ] = [];
                    }
                }
                $servicesOrder = $newServiceOrder;
            }
            Helper::setOption("services_order", json_encode($servicesOrder, JSON_THROW_ON_ERROR));
        }

        return $id;
    }

    /**
     * @throws ServiceCategoryNotFoundException
     * @throws CategoryAlreadyExistException
     * @throws ServiceCategoryParentCannotBeSelfException
     */
    public function update(int $id, ServiceCategoryRequest $request): void
    {
        $category = $this->repository->get($id);

        if ($category === null) {
            throw new ServiceCategoryNotFoundException();
        }

        if ($category->id === $request->getParentId()) {
            throw new ServiceCategoryParentCannotBeSelfException();
        }

        $checkIfNameExist = $this->repository->checkIfNameExist($request->getName(), $request->getParentId(), $id);

        if ($checkIfNameExist) {
            throw new CategoryAlreadyExistException();
        }

        $data = [
            'name' => $request->getName(),
            'parent_id' => $request->getParentId(),
        ];

        $this->repository->update($id, $data);
    }

    /**
     * @return QueryBuilder
     */
    public function getTenantQuery(): QueryBuilder
    {
        return $this->repository->getTenantQuery();
    }

    public function getAllWithParent(): array
    {
        return $this->repository->getAllWithParent();
    }

    /**
     * @param array $ids
     * @return void
     * @throws HasServiceInThisCategoryException
     * @throws RemoveSubCategoryException
     * @throws NoCategorySelectedException
     */
    public function delete(array $ids): void
    {
        if (empty($ids)) {
            throw new NoCategorySelectedException();
        }

        $subCategoryCount = $this->repository->getSubCategoryCount($ids);

        if ($subCategoryCount !== 0) {
            throw new RemoveSubCategoryException();
        }

        $services = $this->repository->getServiceByCategory($ids);

        if ($services !== 0) {
            throw new HasServiceInThisCategoryException();
        }

        $this->repository->delete($ids);
    }

    /**
     * @return array
     */
    public function getAll(): array
    {
        return $this->repository->getAll();
    }
}
