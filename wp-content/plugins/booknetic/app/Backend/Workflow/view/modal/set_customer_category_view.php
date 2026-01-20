<?php

defined('ABSPATH') or die();

use BookneticApp\Providers\Helpers\Helper;

?>

<script type="text/javascript" src="<?php echo Helper::assets('js/set_customer_category.js', 'Workflow')?>"></script>

<div class="fs-modal-title">
    <div class="title-icon badge-lg badge-purple"><i class="fa fa-plus"></i></div>
    <div class="title-text"><?php echo bkntc__('Edit action')?></div>
    <div class="close-btn" data-dismiss="modal"><i class="fa fa-times"></i></div>
</div>

<div class="fs-modal-body">
    <div class="fs-modal-body-inner">
        <form id="editWorkflowActionForm">

            <div class="form-row">
                <div class="form-group col-md-12">
                    <label for="input_customer_set_category"><?php echo bkntc__('Change category to')?></label>
                    <select id="input_customer_set_category" class="form-control">
                        <?php foreach ($parameters['customerCategories'] as $customerCategory): ?>
                            <option value="<?php echo $customerCategory->id; ?>" <?php echo $parameters['category_id'] == $customerCategory->id ? 'selected' : ''; ?> ><?php echo $customerCategory->name; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-12">
                    <input type="checkbox" id="input_run_workflows" <?php echo $parameters["run_workflows"] ? "checked" : "" ?>>
                    <label for="input_run_workflows"><?php echo bkntc__('Run workflows')?></label>
                </div>
            </div>

        </form>
    </div>
</div>

<div class="fs-modal-footer">

    <div class="footer_left_action">
        <input type="checkbox" id="input_is_active" <?php echo $parameters['action_info']->is_active ? 'checked' : '' ?>>
        <label for="input_is_active" class="font-size-14 text-secondary"><?php echo bkntc__('Enabled')?></label>
    </div>

    <button type="button" class="btn btn-lg btn-outline-secondary" data-dismiss="modal"><?php echo bkntc__('CANCEL')?></button>
    <button type="button" class="btn btn-lg btn-primary" id="saveWorkflowActionBtn"><?php echo bkntc__('SAVE')?></button>
</div>
