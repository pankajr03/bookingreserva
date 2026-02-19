<?php

defined('ABSPATH') or die();

use BookneticSaaS\Providers\Helpers\Helper;

$all_pages = get_pages();
?>
<div id="booknetic_settings_area">
	<link rel="stylesheet" href="<?php echo Helper::assets('css/general_settings.css', 'Settings')?>">
	<script type="application/javascript" src="<?php echo Helper::assets('js/general_settings.js', 'Settings')?>"></script>
	<link rel="stylesheet" href="<?php echo Helper::assets('css/bootstrap-select.min.css')?>">
	<script type="application/javascript" src="<?php echo Helper::assets('js/bootstrap-select.min.js')?>"></script>

	<div class="actions_panel clearfix">
		<button type="button" class="btn btn-lg btn-success settings-save-btn float-right"><i class="fa fa-check pr-2"></i> <?php echo bkntcsaas__('SAVE CHANGES')?></button>
	</div>

	<div class="settings-light-portlet">
		<div class="ms-title">
			<?php echo bkntcsaas__('General settings')?>
		</div>
		<div class="ms-content">

			<form class="position-relative">

				<div class="form-row">
					<div class="form-group col-md-6">
						<label for="input_confirmation_number"><?php echo bkntcsaas__('Starting Appointment ID number')?>:</label>
						<input type="text" class="form-control" id="input_confirmation_number" value="<?php echo (int)$parameters['confirmation_number']?>">
					</div>
				</div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="input_google_maps_api_key"><?php echo bkntcsaas__('Google Maps API Key')?>:</label>
                        <input class="form-control" id="input_google_maps_api_key" value="<?php echo Helper::getOption('google_maps_api_key', '');?>">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="input_google_maps_map_id"><?php echo bkntcsaas__('Google Maps Map ID')?>:</label>
                        <input class="form-control" id="input_google_maps_map_id" value="<?php echo Helper::getOption('google_maps_map_id', '');?>">
                    </div>
                </div>

				<div class="form-row">
					<div class="form-group col-md-6">
						<div class="form-control-checkbox">
							<label for="input_google_recaptcha"><?php echo bkntcsaas__('Activate Google reCAPTCHA')?>:</label>
							<div class="fs_onoffswitch">
								<input type="checkbox" class="fs_onoffswitch-checkbox" id="input_google_recaptcha"<?php echo Helper::getOption('google_recaptcha', 'off') == 'on' ? ' checked' : ''?>>
								<label class="fs_onoffswitch-label" for="input_google_recaptcha"></label>
							</div>
						</div>
					</div>
					<div class="form-group col-md-3" data-hide-key="recaptcha">
						<label for="input_google_recaptcha_site_key"><?php echo bkntcsaas__('Site Key')?>:</label>
						<input type="text" class="form-control" id="input_google_recaptcha_site_key" value="<?php echo Helper::getOption('google_recaptcha_site_key', '')?>">
					</div>
					<div class="form-group col-md-3" data-hide-key="recaptcha">
						<label for="input_google_recaptcha_secret_key"><?php echo bkntcsaas__('Secret Key')?>:</label>
						<input type="text" class="form-control" id="input_google_recaptcha_secret_key" value="<?php echo Helper::getOption('google_recaptcha_secret_key', '')?>">
					</div>
				</div>

				<div class="form-row">
					<div class="form-group col-md-6">
						<label>&nbsp;</label>
						<div class="form-control-checkbox">
							<label for="input_enable_language_switcher"><?php echo bkntcsaas__('Enable the Language switcher for tenants')?>:</label>
							<div class="fs_onoffswitch">
								<input type="checkbox" class="fs_onoffswitch-checkbox" id="input_enable_language_switcher"<?php echo Helper::getOption('enable_language_switcher', 'off') == 'on' ? ' checked' : ''?>>
								<label class="fs_onoffswitch-label" for="input_enable_language_switcher"></label>
							</div>
						</div>
					</div>
					<div class="form-group col-md-6" data-hide-key="enable_language_switcher">
						<label for="input_active_languages"><?php echo bkntcsaas__('Select languages')?>:</label>
						<?php
                        echo str_replace(['<select name=', 'value=""'], ['<select class="form-control languagepicker" data-value="'.htmlspecialchars(json_encode(Helper::getOption('active_languages', []))).'" data-live-search="true" multiple name=', 'value="en_US"'], wp_dropdown_languages([
                            'id'        =>  'input_active_languages',
                            'echo'      =>  false,
                            'selected'  =>  '-'
                        ]));
?>
					</div>
				</div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <div class="form-control-checkbox">
                            <label for="input_new_wp_user_on_new_booking"><?php echo bkntc__('Create a new wordpress user on new booking')?>:</label>
                            <div class="fs_onoffswitch">
                                <input type="checkbox" class="fs_onoffswitch-checkbox" id="input_new_wp_user_on_new_booking"<?php echo Helper::getOption('new_wp_user_on_new_booking', 'off') == 'on' ? ' checked' : ''?>>
                                <label class="fs_onoffswitch-label" for="input_new_wp_user_on_new_booking"></label>
                            </div>
                        </div>
                    </div>
                    <div class="form-group col-md-6">
                        <div class="form-control-checkbox">
                            <label for="input_disallow_tenants_to_enter_wp_dashboard"><?php echo bkntc__('Disallow tenants to enter WordPress dashboard')?>:</label>
                            <div class="fs_onoffswitch">
                                <input type="checkbox" class="fs_onoffswitch-checkbox" id="input_disallow_tenants_to_enter_wp_dashboard"<?php echo Helper::getOption('disallow_tenants_to_enter_wp_dashboard', 'off') == 'on' ? ' checked' : ''?>>
                                <label class="fs_onoffswitch-label" for="input_disallow_tenants_to_enter_wp_dashboard"></label>
                            </div>
                        </div>
                    </div>
                </div>

			</form>

		</div>
	</div>
</div>