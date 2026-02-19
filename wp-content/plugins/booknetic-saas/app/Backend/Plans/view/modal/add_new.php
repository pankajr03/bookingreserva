<?php

defined('ABSPATH') or die();

use BookneticSaaS\Providers\Helpers\Helper;
use BookneticSaaS\Providers\UI\TabUI;

?>

<link rel="stylesheet" href="<?php echo Helper::assets('css/add_new.css', 'Plans')?>">
<script type="application/javascript" src="<?php echo Helper::assets('js/add_new.js', 'Plans')?>" id="add_new_JS" data-mn="<?php echo $_mn?>" data-plan-id="<?php echo (int)$parameters['plan']['id']?>"></script>

<div class="fs-modal-title">
    <div class="title-icon badge-lg badge-purple"><i class="fa fa-plus"></i></div>
    <div class="title-text"><?php echo bkntcsaas__('Add Plan')?></div>
    <div class="close-btn" data-dismiss="modal"><i class="fa fa-times"></i></div>
</div>

<div class="fs-modal-body">
    <div class="fs-modal-body-inner">
        <form id="addPlanForm">
            <div class="nowrap overflow-auto">
                <ul class="nav nav-tabs nav-light" data-tab-group="plans_add">
                    <?php foreach (TabUI::get('plans_add')->getSubItems() as $tab): ?>
                        <li class="nav-item"><a class="nav-link " data-tab="<?php echo $tab->getSlug(); ?>" href="#"><?php echo $tab->getTitle(); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="tab-content mt-5">
                <?php foreach (TabUI::get('plans_add')->getSubItems() as $tab): ?>
                    <div class="tab-pane " data-tab-content="plans_add_<?php echo $tab->getSlug(); ?>" id="tab_<?php echo $tab->getSlug(); ?>"><?php echo $tab->getContent($parameters); ?></div>
                <?php endforeach; ?>
            </div>
        </form>
    </div>
</div>

<div class="fs-modal-footer">
    <button type="button" class="btn btn-lg btn-outline-secondary" data-dismiss="modal"><?php echo bkntcsaas__('CANCEL')?></button>
    <button type="button" class="btn btn-lg btn-primary" id="addPlanSave"><?php echo $parameters['id'] ? bkntcsaas__('SAVE PLAN') : bkntcsaas__('ADD PLAN')?></button>
</div>