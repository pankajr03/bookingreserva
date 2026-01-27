<?php

defined('ABSPATH') or die();

use BookneticApp\Backend\Customers\DTOs\Response\CustomerResponse;
use BookneticApp\Providers\Helpers\Helper;

/**
 * @var array $parameters
 * @var CustomerResponse $customer
 */
$customer = $parameters['customer'];
?>
<div class="modal_payment">
    <div class="modal_payment-header d-flex justify-content-between align-items-center pb-4">
        <div class="modal_payment-profile d-flex align-items-center">
            <img src="<?php echo Helper::profileImage($customer->getProfileImage(), 'Customers'); ?>" alt="">
            <span><?php echo htmlspecialchars($customer->getFirstName() . ' ' . $customer->getLastName()); ?></span>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-lg-6">
            <div class="modal_payment-card">
                <h6><?php echo bkntc__('Email') ?></h6>
                <span class="text-break"><?php echo $customer->getEmail() ?? bkntc__('N/A'); ?></span>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="modal_payment-card">
                <h6><?php echo bkntc__('Phone') ?></h6>
                <span><?php echo $customer->getPhoneNumber() ?? bkntc__('N/A'); ?></span>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="modal_payment-card">
                <h6><?php echo bkntc__('Gender') ?></h6>
                <span><?php echo $customer->getGender() !== null ? bkntc__(ucfirst($customer->getGender())) : bkntc__('N/A'); ?></span>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="modal_payment-card">
                <h6><?php echo bkntc__('Birthdate') ?></h6>
                <span><?php echo $customer->getBirthdate() ?? bkntc__('N/A'); ?></span>
            </div>
        </div>

        <div class="col-lg-12 mt-3">
            <div class="modal_payment-card">
                <h6><?php echo bkntc__('Note') ?></h6>
                <span><?php echo htmlspecialchars($customer->getNotes()) ?? bkntc__('N/A'); ?></span>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="modal_payment-card">
                <h6><?php echo bkntc__('Category') ?></h6>
                <span><?php echo $customer->getCategoryName() !== null ? bkntc__(ucfirst($customer->getCategoryName())) : ''; ?></span>
            </div>
        </div>
    </div>
</div>
