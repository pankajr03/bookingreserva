<?php

defined('ABSPATH') or die();

use BookneticApp\Backend\Customers\DTOs\Response\CustomerCategoryResponse;

/**
 * @var CustomerCategoryResponse $parameters
 */
?>
<div class="modal_payment">
    <div class="row mt-3">
        <div class="col-md-6">
            <h6><?php echo bkntc__('Name') ?></h6>
            <span><?php echo htmlspecialchars($parameters->getName()); ?></span>
        </div>
        <div class="col-lg-6">
            <div class="modal_payment-card">
                <h6><?php echo bkntc__('Color') ?></h6>
                <div class="appointment-status-icon ml-3" style="background-color:<?php echo htmlspecialchars($parameters->getColor()); ?> "></div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="modal_payment-card">
                <h6><?php echo bkntc__('Icon') ?></h6>
                <span><i class="<?php echo htmlspecialchars($parameters->getIcon()) ?>"></i></span>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="modal_payment-card">
                <h6><?php echo bkntc__('Is Default') ?></h6>
                <?php echo $parameters->isDefault() ? '<i class="fa fa-star is_default" title="' . bkntc__('Default Role') . '"></i>' : ''; ?>
            </div>
        </div>

        <div class="col-lg-12">
            <div class="modal_payment-card">
                <h6><?php echo bkntc__('Note') ?></h6>
              <p><?php echo htmlspecialchars($parameters->getNote()) ?></p>
            </div>
        </div>

    </div>
</div>
