<?php
defined('ABSPATH') or die();

use BookneticApp\Providers\Helpers\Helper;

/**
 * @var mixed $parameters
 */
?>

<link rel="stylesheet" href="<?php echo Helper::assets('css/add_new_category.css', 'Services') ?>">
<script type="application/javascript" src="<?php echo Helper::assets('js/add_new_category.js', 'Services') ?>"></script>

<div class="fs-modal-title">
    <div class="title-icon badge-lg badge-purple"><i class="fa fa-plus"></i></div>
    <div class="title-text"><?php echo bkntc__('Add Category') ?></div>
    <div class="close-btn" data-dismiss="modal"><i class="fa fa-times"></i></div>
</div>

<div class="fs-modal-body">

    <div class="fs-modal-body-inner">
        <form id="addServiceForm">
            <div class="form-row">

                <?php if (isset($parameters['category']) && isset($parameters['category']->id)): ?>
                    <input type="hidden" id="category_id" name="id" value="<?php echo (int)$parameters['category']->id ?>">
                <?php else: ?>
                    <input type="hidden" id="category_id" name="id" value="0">
                <?php endif; ?>

                <div class="form-group col-md-12">
                    <label for="new_category_name"><?php echo bkntc__('Category name') ?> <span class="required-star">*</span></label>
                    <input type="text"
                           class="form-control"
                           data-multilang="true"
                           data-multilang-fk="0"
                           id="new_category_name"
                           value="<?php echo isset($parameters['category']) ? htmlentities($parameters['category']->name, ENT_QUOTES | ENT_HTML5, 'UTF-8') : '' ?>">
                </div>
            </div>
        </form>

    </div>
</div>

<div class="fs-modal-footer">
    <button type="button" class="btn btn-lg btn-default" data-dismiss="modal"><?php echo bkntc__('CLOSE') ?></button>
    <button type="button" class="btn btn-lg btn-primary" id="save_new_category"><?php echo bkntc__('SAVE') ?></button>
</div>
