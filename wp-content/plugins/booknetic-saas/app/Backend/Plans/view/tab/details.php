<?php

defined('ABSPATH') or die();

/**
 * @var array $parameters
 */
use BookneticSaaS\Providers\Helpers\Helper;

?>
<div class="form-row">
    <div class="form-group col-md-6">
        <label for="input_name"><?php echo bkntcsaas__('Name')?> <span class="required-star">*</span></label>
        <input type="text" class="form-control" id="input_name" value="<?php echo htmlspecialchars($parameters['plan']['name'])?>">
    </div>
    <div class="form-group col-md-6">
        <label for="input_ribbon_text"><?php echo bkntcsaas__('Bookmark')?></label>
        <input type="text" class="form-control" id="input_ribbon_text" maxlength="15" value="<?php echo htmlspecialchars($parameters['plan']['ribbon_text'])?>">
    </div>
</div>

<div class="form-row">
    <div class="form-group col-md-6 position-relative">
        <label for="input_color"><?php echo bkntcsaas__('Color')?> <span class="required-star">*</span></label>
        <input type="text" class="form-control" id="input_color" value="<?php echo htmlspecialchars($parameters['plan']['color'])?>">
        <span class="plan_color"></span>
        <div class="fs-popover" id="plan_color_panel">
            <div class="fs-popover-title">
                <span><?php echo bkntcsaas__('Select colors')?></span>
                <img src="<?php echo Helper::icon('cross.svg')?>" class="close-popover-btn" alt="">
            </div>
            <div class="fs-popover-content">
                <div class="fs-plan-colors-line">
                    <div class="color-rounded color-r-1<?php echo ($parameters['plan']['color'] == '#53d56c' ? ' selected-color' : '')?>" data-color="#53d56c"></div>
                    <div class="color-rounded color-r-2<?php echo ($parameters['plan']['color'] == '#26c0d6' ? ' selected-color' : '')?>" data-color="#26c0d6"></div>
                    <div class="color-rounded color-r-3<?php echo ($parameters['plan']['color'] == '#fd9b78' ? ' selected-color' : '')?>" data-color="#fd9b78"></div>
                    <div class="color-rounded color-r-4<?php echo ($parameters['plan']['color'] == '#cc65aa' ? ' selected-color' : '')?>" data-color="#cc65aa"></div>
                    <div class="color-rounded color-r-5<?php echo ($parameters['plan']['color'] == '#2078fa' ? ' selected-color' : '')?>" data-color="#2078fa"></div>
                </div>
                <div class="fs-plan-colors-line mt-3">
                    <div class="color-rounded color-r-6<?php echo ($parameters['plan']['color'] == '#947bbf' ? ' selected-color' : '')?>" data-color="#947bbf"></div>
                    <div class="color-rounded color-r-7<?php echo ($parameters['plan']['color'] == '#c9c2b8' ? ' selected-color' : '')?>" data-color="#c9c2b8"></div>
                    <div class="color-rounded color-r-8<?php echo ($parameters['plan']['color'] == '#527dde' ? ' selected-color' : '')?>" data-color="#527dde"></div>
                    <div class="color-rounded color-r-9<?php echo ($parameters['plan']['color'] == '#425a64' ? ' selected-color' : '')?>" data-color="#425a64"></div>
                    <div class="color-rounded color-r-10<?php echo ($parameters['plan']['color'] == '#ffbb44' ? ' selected-color' : '')?>" data-color="#ffbb44"></div>
                </div>

                <div class="form-row mt-3">
                    <div class="form-group col-md-12">
                        <label for="input_color_hex"><?php echo bkntcsaas__('Hex')?></label>
                        <input type="text" class="form-control" id="input_color_hex" value="#53d56c">
                    </div>
                </div>

                <div class="fs-popover-footer">
                    <button type="button" class="btn btn-default btn-lg close-btn1"><?php echo bkntcsaas__('CLOSE')?></button>
                    <button type="button" class="btn btn-primary btn-lg ml-2 save-btn1"><?php echo bkntcsaas__('SAVE')?></button>
                </div>

            </div>
        </div>
    </div>
    <div class="form-group col-md-6">
        <label for="input_order_by"><?php echo bkntcsaas__('Order number')?></label>
        <input type="text" class="form-control" id="input_order_by" value="<?php echo htmlspecialchars($parameters['plan']['order_by'])?>">
    </div>
</div>

<div class="form-row">
    <div class="form-group col-md-6">
        <label for="input_monthly_price"><?php echo bkntcsaas__('Monthly price')?> (<?php echo Helper::currencySymbol()?>)</label>
        <input type="text" class="form-control" id="input_monthly_price" value="<?php echo htmlspecialchars($parameters['plan']['monthly_price'])?>"<?php echo empty($parameters['plan']['stripe_product_data']) ? '' : ' readonly'?>>
    </div>
    <div class="form-group col-md-6">
        <label for="input_monthly_price_discount"><?php echo bkntcsaas__('Discount for first month')?> (%)</label>
        <input type="text" class="form-control" id="input_monthly_price_discount" value="<?php echo htmlspecialchars($parameters['plan']['monthly_price_discount'])?>"<?php echo empty($parameters['plan']['stripe_product_data']) ? '' : ' readonly'?>>
    </div>
</div>

<div class="form-row">
    <div class="form-group col-md-6">
        <label for="input_annually_price"><?php echo bkntcsaas__('Annual price')?> (<?php echo Helper::currencySymbol()?>)</label>
        <input type="text" class="form-control" id="input_annually_price" value="<?php echo htmlspecialchars($parameters['plan']['annually_price'])?>"<?php echo empty($parameters['plan']['stripe_product_data']) ? '' : ' readonly'?>>
    </div>
    <div class="form-group col-md-6">
        <label for="input_annually_price_discount"><?php echo bkntcsaas__('Discount for first year')?> (%)</label>
        <input type="text" class="form-control" id="input_annually_price_discount" value="<?php echo htmlspecialchars($parameters['plan']['annually_price_discount'])?>"<?php echo empty($parameters['plan']['stripe_product_data']) ? '' : ' readonly'?>>
    </div>
</div>

<div class="form-row">
    <div class="form-group col-md-12">
        <div class="form-control-checkbox">
            <label for="input_is_active"><?php echo bkntcsaas__('Hidden plan')?></label>
            <div class="fs_onoffswitch">
                <input type="checkbox" class="fs_onoffswitch-checkbox" id="input_is_active"<?php echo $parameters['plan']['is_active'] ? '' : ' checked'?>>
                <label class="fs_onoffswitch-label" for="input_is_active"></label>
            </div>
        </div>
    </div>
</div>

<div class="form-row">
    <div class="form-group col-md-12">
        <label for="input_description"><?php echo bkntcsaas__('Description')?></label>
        <textarea class="form-control" id="input_description"><?php echo htmlspecialchars($parameters['plan']['description'])?></textarea>
    </div>
</div>