<?php

defined('ABSPATH') or die();

use BookneticApp\Backend\Customers\DTOs\Response\CustomerCategoryResponse;
use BookneticApp\Providers\Helpers\Helper;

/**
 * @var array $parameters
 * @var CustomerCategoryResponse $customerCategory`
 */
$customerCategory = $parameters['customerCategory'];

$name = $customerCategory->getName();
$icon = $customerCategory->getIcon();
$color = $customerCategory->getColor();
$isDefault = $customerCategory->isDefault();
$note = $customerCategory->getNote();

$uncategorizedCustomerCount = $parameters['uncategorizedCustomerCount'];

$is_default_tooltip = bkntc__("When a new customer makes a booking, 
    this category will be automatically assigned to them. O
    nly one category can be selected as the default at a time. 
    If you've already set another category as default, this one will replace it.");

$uncategorizedCustomerCountTooltip = bkntc__("You currently have %d uncategorized customers. If you enable this option, all of them will be automatically assigned to this category.", [$uncategorizedCustomerCount]);
?>

<link rel="stylesheet" href="<?php echo Helper::assets('css/add_new.css', 'Customers') ?>">

<div class="form-row">
    <div class="form-group col-md-12">
        <label for="input_name"><?php echo bkntc__('Name') ?> <span class="required-star">*</span></label>
        <input type="text" class="form-control" id="input_name" value="<?php echo $name ?>">
    </div>

    <div class="form-group col-md-6">
        <label for="input_icon"><?php echo bkntc__('Icon') ?> <span class="required-star">*</span></label>
        <input type="text" class="form-control" id="input_icon" value="<?php echo $icon ?>">
    </div>
    <div class="form-group col-md-6 position-relative">
        <label for="input_color_<?php echo 1; ?>"><?php echo bkntc__('Color'); ?>: <span class="required-star">*</span></label>
        <input type="text"
               class="form-control input_color"
               id="input_color"
               value="<?php echo htmlspecialchars($color); ?>">
    </div>

    <div class="form-group col-md-12 position-relative">
        <label for="input_color_<?php echo 1; ?>"><?php echo bkntc__('Note'); ?></label>
        <textarea name="input_note" class="form-control" id="input_note" cols="30" rows="10"><?php echo htmlspecialchars($note); ?></textarea>
    </div>

    <div class="form-group col-md-12 d-flex">
        <input id="input_is_default"
               type="checkbox"
               name="input_is_default"
                <?php echo $isDefault ? 'checked' : ''; ?>>

        <label for="input_is_default"><?php echo bkntc__('Make default for new customers'); ?></label>
        <i class="fa fa-info-circle help-icon do_tooltip p-0" data-content="<?php echo bkntc__($is_default_tooltip); ?>"></i>
    </div>

    <?php if ($uncategorizedCustomerCount) { ?>
        <div class="form-group col-md-12  <?php echo !$isDefault ? 'd-none' : ''; ?> " id="uncategorized-customers">
            <input id="default_for_new_customers"
                   type="checkbox"
                   name="input_default_for_new_customers">
            <label for="input_default_for_new_customers"><?php echo bkntc__('Assign uncategorized customers to this category'); ?></label>
            <i class="fa fa-info-circle help-icon do_tooltip p-0" data-content="<?php echo bkntc__($uncategorizedCustomerCountTooltip); ?>"></i>

        </div>
    <?php } ?>
</div>




