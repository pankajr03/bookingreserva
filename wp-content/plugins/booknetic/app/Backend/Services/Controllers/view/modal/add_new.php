<?php

defined('ABSPATH') or die();

use BookneticApp\Backend\Services\DTOs\Response\ServiceCategoryViewResponse;
use BookneticApp\Providers\Helpers\Helper;

/**
 * @var ServiceCategoryViewResponse $parameters
 */
?>

<link rel="stylesheet" href="<?php echo Helper::assets('css/add_new_category.css', 'Services') ?>">
<script type="application/javascript" src="<?php echo Helper::assets('js/add_new_category.js', 'Services') ?>"
        id="add_new_JS"
        data-category-id="<?php echo $parameters->getServiceCategory()->getId() ?>"></script>

<div class="fs-modal-title">
    <div class="title-icon badge-lg badge-purple"><i class="fa fa-plus"></i></div>
    <div class="title-text"><?php echo bkntc__('Add Category') ?></div>
    <div class="close-btn" data-dismiss="modal"><i class="fa fa-times"></i></div>
</div>

<div class="fs-modal-body">
    <div class="fs-modal-body-inner">
        <form id="addServiceForm">

            <div class="form-row">
                <div class="form-group col-md-12">
                    <label for="input_parent_category"><?php echo bkntc__('Parent category') ?> <span
                                class="required-star">*</span></label>
                    <select id="input_parent_category" class="form-control">
                        <option value="0"><?php echo bkntc__('Root category') ?></option>
                        <?php
                        foreach ($parameters->getCategories() as $category) {
                            echo '<option value="' . $category->getId() . '"' . ($parameters->getServiceCategory()->getParentId() !== null && $parameters->getServiceCategory()->getParentId() === $category->getId() ? ' selected' : '') . '>' . htmlspecialchars($category->getName()) . '</option>';
                        }
?>
                    </select>
                </div>
                <div class="form-group col-md-12">
                    <label for="new_category_name"><?php echo bkntc__('Category name') ?> <span
                                class="required-star">*</span></label>
                    <input type="text" class="form-control"
                           value="<?php echo $parameters->getServiceCategory()->getName() ?>" data-multilang="true"
                           data-multilang-fk="0" id="new_category_name">
                </div>
            </div>

        </form>
    </div>
</div>

<div class="fs-modal-footer">
    <button type="button" class="btn btn-lg btn-default" data-dismiss="modal"><?php echo bkntc__('CLOSE') ?></button>
    <button type="button" class="btn btn-lg btn-primary" id="save_new_category"><?php echo bkntc__('SAVE') ?></button>
</div>
