<?php

namespace BookneticApp\Backend\Services\Controllers;

use BookneticApp\Backend\Services\DTOs\Request\ServiceCategoryRequest;
use BookneticApp\Backend\Services\DTOs\Response\ServiceCategoryResponse;
use BookneticApp\Backend\Services\DTOs\Response\ServiceCategoryViewResponse;
use BookneticApp\Backend\Services\Exceptions\CategoryAlreadyExistException;
use BookneticApp\Backend\Services\Exceptions\HasServiceInThisCategoryException;
use BookneticApp\Backend\Services\Exceptions\NameRequiredException;
use BookneticApp\Backend\Services\Exceptions\NoCategorySelectedException;
use BookneticApp\Backend\Services\Exceptions\RemoveSubCategoryException;
use BookneticApp\Backend\Services\Exceptions\ServiceCategoryNotFoundException;
use BookneticApp\Backend\Services\Services\ServiceCategoryService;
use BookneticApp\Models\ServiceCategory;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Core\CapabilitiesException;
use BookneticApp\Providers\Core\Controller;
use BookneticApp\Providers\Request\Post;
use JsonException;

class ServiceCategoryAjaxController extends Controller
{
    private ServiceCategoryService $service;

    public function __construct()
    {
        $this->service = new ServiceCategoryService();
    }

    public function add_new()
    {
        $id = Post::int('id');

        if ($id > 0) {
            Capabilities::must('services_edit_category');

            $serviceCategory = $this->service->get($id);
        } else {
            Capabilities::must('services_add_category');

            $serviceCategory = ServiceCategoryResponse::createEmpty();
        }

        $allCategories = $this->service->getAll();

        ServiceCategory::handleTranslation($id);

        $viewResponse = new ServiceCategoryViewResponse();

        $viewResponse->setServiceCategory($serviceCategory);
        $viewResponse->setCategories($allCategories);

        return $this->modalView('add_new', $viewResponse);
    }

    /**
     * @throws NameRequiredException
     * @throws JsonException
     * @throws CategoryAlreadyExistException
     */
    public function create()
    {
        $request = $this->prepareSaveRequestDTO();

        $id = $this->service->create($request);

        return $this->response(true, [
            'id' => $id
        ]);
    }

    /**
     * @throws ServiceCategoryNotFoundException|CategoryAlreadyExistException|NameRequiredException
     */
    public function update()
    {
        $id      = Post::int('id');
        $request = $this->prepareSaveRequestDTO();

        $this->service->update($id, $request);

        return $this->response(true, [
            'id' => $id
        ]);
    }

    /**
     * @throws NameRequiredException
     */
    private function prepareSaveRequestDTO(): ServiceCategoryRequest
    {
        $name   = Post::string('name');
        $parent = Post::int('parent_id');

        $request = new ServiceCategoryRequest();

        $request->setName($name)
            ->setParentId($parent);

        return $request;
    }

    /**
     * @throws RemoveSubCategoryException
     * @throws NoCategorySelectedException
     * @throws HasServiceInThisCategoryException
     * @throws CapabilitiesException
     */
    public function delete()
    {
        Capabilities::must('services_delete_category');

        $ids = Post::array('ids');

        $this->service->delete($ids);

        return $this->response(true);
    }
}
