<?php

defined('ABSPATH') or die();

use BookneticApp\Providers\Core\Permission;
use BookneticApp\Providers\Helpers\Helper;

/**
 * @var mixed $parameters
 */

// doit burda saas ve regular biraz qarisib
?>

<link rel="stylesheet" href="<?php echo \BookneticSaaS\Providers\Helpers\Helper::assets('css/share_page.css', 'Billing')?>">
<script type="application/javascript" src="<?php echo \BookneticSaaS\Providers\Helpers\Helper::assets('js/share_page.js', 'Billing')?>"></script>

<div class="fs-modal-title">
	<div class="title-icon badge-lg badge-purple"><i class="fa fa-share"></i></div>
	<div class="title-text"><?php echo bkntcsaas__('Share your page')?></div>
	<div class="close-btn" data-dismiss="modal"><i class="fa fa-times"></i></div>
</div>

<div class="fs-modal-body">
	<div class="fs-modal-body-inner">

		<div class="form-row">
			<div class="form-group col-md-12">
				<label for="input_booking_page_url"><?php echo bkntcsaas__('Your page URL')?>:</label>
				<input type="text" id="input_booking_page_url" readonly class="form-control" value="<?php echo Permission::getTenantBookingPageURL() ?>">
				<a href="mailto:?subject=<?php echo rawurlencode(bkntcsaas__('Schedule time with me'))?>&body=<?php echo rawurlencode(bkntcsaas__('You can see my real-time availability and schedule time with me at %s', [ site_url() . '/' . htmlspecialchars(Permission::tenantInf()->domain) ]))?>" class="btn btn-primary mt-2"><?php echo bkntcsaas__('Send link via Email')?></a>
			</div>
		</div>

		<div class="form-row">
			<div class="form-group col-md-12">
				<label for="input_booking_page_url"><?php echo bkntcsaas__('The QR for your page')?>:</label>
				<div class="qr_code_image">
                    <img id="qr_code" src="<?php echo 'https://quickchart.io/qr?text=' . urlencode(site_url() . '/' . htmlspecialchars(Permission::tenantInf()->domain)) ?>">
				</div>
                <button id="download_qr" class="btn btn-primary mt-2"><?php echo bkntcsaas__('Click to download QR')?></button>
			</div>
		</div>

		<div class="form-row">
			<div class="form-group col-md-12">
				<label for="input_booking_enbed"><?php echo bkntcsaas__('Add to your Website')?>:</label>
				<textarea id="input_booking_enbed" readonly class="form-control"><!-- <?php echo Helper::getOption('powered_by', 'Booknetic', false)?> iframe --><iframe src="<?php echo site_url() . '/' . htmlspecialchars(Permission::tenantInf()->domain)?>?iframe=1" style="max-width:1001px;height:<?php echo $parameters['height'] ?>px;width: 100%;"></iframe><!-- <?php echo Helper::getOption('powered_by', 'Booknetic', false)?> iframe --></textarea>
			</div>
		</div>

        <?php do_action('bkntcsaas_share_page_footer') ?>

	</div>
</div>

<div class="fs-modal-footer">
	<button type="button" class="btn btn-lg btn-outline-secondary" data-dismiss="modal"><?php echo bkntcsaas__('CLOSE')?></button>
</div>



