<?php

defined('ABSPATH') or die();

use BookneticSaaS\Providers\Helpers\Helper;

$all_pages = get_pages();
?>
<div id="booknetic_settings_area">
	<link rel="stylesheet" href="<?php echo Helper::assets('css/general_settings.css', 'Settings')?>">
	<script type="application/javascript" src="<?php echo Helper::assets('js/page_settings.js', 'Settings')?>"></script>
	<link rel="stylesheet" href="<?php echo Helper::assets('css/bootstrap-select.min.css')?>">
	<script type="application/javascript" src="<?php echo Helper::assets('js/bootstrap-select.min.js')?>"></script>

	<div class="actions_panel clearfix">
		<button type="button" class="btn btn-lg btn-success settings-save-btn float-right"><i class="fa fa-check pr-2"></i> <?php echo bkntcsaas__('SAVE CHANGES')?></button>
	</div>

	<div class="settings-light-portlet">
		<div class="ms-title">
			<?php echo bkntcsaas__('Page settings')?>
		</div>
		<div class="ms-content">

			<form class="position-relative">

				<div class="form-row">
					<div class="form-group col-md-6">
						<label for="input_sign_in_page"><?php echo bkntcsaas__('Sign-in page')?>:</label>
						<select class="form-control" id="input_sign_in_page">
							<?php foreach ($all_pages as $page) : ?>
								<option value="<?php echo htmlspecialchars($page->ID)?>"<?php echo Helper::getOption('sign_in_page', '') == $page->ID ? ' selected' : ''?>><?php echo htmlspecialchars(empty($page->post_title) ? '-' : $page->post_title)?> (ID: <?php echo $page->ID?>)</option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="form-group col-md-6">
						<label for="input_sign_up_page"><?php echo bkntcsaas__('Sign-Up page')?>:</label>
						<select class="form-control" id="input_sign_up_page">
							<?php foreach ($all_pages as $page) : ?>
								<option value="<?php echo htmlspecialchars($page->ID)?>"<?php echo Helper::getOption('sign_up_page', '') == $page->ID ? ' selected' : ''?>><?php echo htmlspecialchars(empty($page->post_title) ? '-' : $page->post_title)?> (ID: <?php echo $page->ID?>)</option>
							<?php endforeach; ?>
						</select>
					</div>
                    <div class="form-group col-md-6">
                        <label for="input_forgot_password_page"><?php echo bkntcsaas__('Forgot Password page')?>:</label>
                        <select class="form-control" id="input_forgot_password_page">
                            <?php foreach ($all_pages as $page) : ?>
                                <option value="<?php echo htmlspecialchars($page->ID)?>"<?php echo Helper::getOption('forgot_password_page', '') == $page->ID ? ' selected' : ''?>><?php echo htmlspecialchars(empty($page->post_title) ? '-' : $page->post_title)?> (ID: <?php echo $page->ID?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="input_change_status_page_id"><?php echo bkntc__('Change Appointment Status Page')?>:</label>
                        <select class="form-control" id="input_change_status_page_id">
                            <?php foreach (get_pages() as $page) : ?>
                                <option value="<?php echo htmlspecialchars($page->ID)?>"<?php echo Helper::getOption('change_status_page_id', '', false) == $page->ID ? ' selected' : ''?>><?php echo htmlspecialchars(empty($page->post_title) ? '-' : $page->post_title)?> (ID: <?php echo $page->ID?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
				</div>

                <div class="form-row">
                    <div class="form-group col-md-12">
                        <label for="input_booking_page"><?php echo bkntcsaas__('Booking page')?>:</label>
                        <select class="form-control" id="input_booking_page">
                            <?php foreach ($all_pages as $page) : ?>
                                <option value="<?php echo htmlspecialchars($page->ID)?>"<?php echo Helper::getOption('booking_page', '') == $page->ID ? ' selected' : ''?>><?php echo htmlspecialchars(empty($page->post_title) ? '-' : $page->post_title)?> (ID: <?php echo $page->ID?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="input_regular_sign_in_page"><?php echo bkntcsaas__('Sign-in page for customers')?>:</label>
                        <select class="form-control" id="input_regular_sign_in_page">
                            <?php foreach ($all_pages as $page) : ?>
                                <option value="<?php echo htmlspecialchars($page->ID)?>"<?php echo Helper::getOption('regular_sing_in_page', '') == $page->ID ? ' selected' : ''?>><?php echo htmlspecialchars(empty($page->post_title) ? '-' : $page->post_title)?> (ID: <?php echo $page->ID?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="input_regular_sign_up_page"><?php echo bkntcsaas__('Sign-Up page for customers')?>:</label>
                        <select class="form-control" id="input_regular_sign_up_page">
                            <?php foreach ($all_pages as $page) : ?>
                                <option value="<?php echo htmlspecialchars($page->ID)?>"<?php echo Helper::getOption('regular_sign_up_page', '') == $page->ID ? ' selected' : ''?>><?php echo htmlspecialchars(empty($page->post_title) ? '-' : $page->post_title)?> (ID: <?php echo $page->ID?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="input_regular_forgot_password_page"><?php echo bkntcsaas__('Forgot Password page for customers')?>:</label>
                        <select class="form-control" id="input_regular_forgot_password_page">
                            <?php foreach ($all_pages as $page) : ?>
                                <option value="<?php echo htmlspecialchars($page->ID)?>"<?php echo Helper::getOption('regular_forgot_password_page', '') == $page->ID ? ' selected' : ''?>><?php echo htmlspecialchars(empty($page->post_title) ? '-' : $page->post_title)?> (ID: <?php echo $page->ID?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                </div>


			</form>

		</div>
	</div>
</div>