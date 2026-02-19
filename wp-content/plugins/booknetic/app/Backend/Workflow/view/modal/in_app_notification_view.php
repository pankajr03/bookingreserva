<?php

defined('ABSPATH') or die();

use BookneticApp\Providers\Helpers\Helper;

?>
<script>
    var workflow_in_app_notification_action_all_shortcodes = <?php echo json_encode($parameters['all_shortcodes']) ?>;

    var workflow_in_app_notification_action_all_shortcodes_obj = {};
    workflow_in_app_notification_action_all_shortcodes.forEach((value,index)=>{
        workflow_in_app_notification_action_all_shortcodes_obj[value.code] = value.name;
    });
</script>
<script src="<?php echo Helper::assets('plugins/summernote/summernote-lite.min.js')?>"></script>
<link rel="stylesheet" href="<?php echo Helper::assets('plugins/summernote/summernote-lite.min.css')?>" type="text/css">
<script src="<?php echo Helper::assets('js/summernote.js')?>"></script>
<link rel="stylesheet" href="<?php echo Helper::assets('css/summernote.css')?>" type="text/css">
<script type="text/javascript" src="<?php echo Helper::assets('js/in_app_notification.js', 'Workflow')?>"></script>

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
                    <label for="input_to"><?php echo bkntc__('To')?></label>
                    <select id="input_to" class="form-control" multiple>
                        <?php foreach ($parameters['users'] as $user) :?>
                            <option value="<?php echo $user['ID']?>" <?php echo in_array($user['ID'], $parameters['to']) ? 'selected' : ''; ?> ><?php echo $user['user_login']; ?></option>
                        <?php endforeach;?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-12">
                    <label for="input_title"><?php echo bkntc__('Title')?></label>
                    <input type="text" id="input_title" class="form-control" value="<?php echo $parameters["title"]?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-12">
                    <label for="input_message"><?php echo bkntc__('Message')?></label>
                    <textarea class="form-control required" id="input_message"><?php echo empty($parameters["message"]) ? '' : htmlspecialchars($parameters["message"]);?></textarea>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-12">
                    <label for="input_status"><?php echo bkntc__('Status')?></label>
                    <select id="input_status" class="form-control">
                        <option value="success" <?php echo $parameters['status'] === 'success' ? 'selected' : ''; ?> ><?php echo bkntc__('Success'); ?></option>
                        <option value="fail" <?php echo $parameters['status'] === 'fail' ? 'selected' : ''; ?> ><?php echo bkntc__('Fail'); ?></option>
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
