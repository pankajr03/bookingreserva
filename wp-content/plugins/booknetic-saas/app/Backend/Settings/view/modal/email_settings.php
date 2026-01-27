<?php

defined('ABSPATH') or die();

use BookneticSaaS\Providers\Helpers\Helper;

/**
 * @var array $parameters
*/
$authorized = !!$parameters['authorized'];
$email = $parameters['email'];
$errors = $parameters['errors'] ?? [];

?>
<div id="booknetic_settings_area">
	<link rel="stylesheet" href="<?php print Helper::assets('css/email_settings.css', 'Settings')?>">
	<script type="application/javascript" src="<?php print Helper::assets('js/email_settings.js', 'Settings')?>"></script>

	<div class="actions_panel clearfix">
		<button type="button" class="btn btn-lg btn-success settings-save-btn float-right"><i class="fa fa-check pr-2"></i> <?php print bkntcsaas__('SAVE CHANGES')?></button>
	</div>

	<div class="settings-light-portlet">
		<div class="ms-title">
			<?php print bkntcsaas__('Email settings')?>
		</div>
		<div class="ms-content">

			<div class="form-row">
				<div class="form-group col-md-6">
					<label for="input_mail_gateway"><?php echo bkntcsaas__('Mail Gateway')?>:</label>
					<select class="form-control" id="input_mail_gateway">
						<option value="wp_mail"<?php echo (Helper::getOption('mail_gateway', 'wp_mail') == 'wp_mail' ? ' selected' : '')?>><?php echo bkntcsaas__('WordPress Mail')?></option>
						<option value="smtp"<?php echo (Helper::getOption('mail_gateway', 'wp_mail') == 'smtp' ? ' selected' : '')?>><?php echo bkntcsaas__('SMTP')?></option>
						<option value="gmail_smtp"<?php echo (Helper::getOption('mail_gateway', 'wp_mail') == 'gmail_smtp' ? ' selected' : '')?>><?php echo bkntcsaas__('Gmail SMTP')?></option>
					</select>
				</div>
			</div>

			<div class="smtp_details dashed-border">
				<div class="form-row">
					<div class="form-group col-md-6">
						<label for="input_smtp_hostname"><?php echo bkntcsaas__('SMTP Hostname')?>:</label>
						<input class="form-control" id="input_smtp_hostname" value="<?php echo htmlspecialchars(Helper::getOption('smtp_hostname', '', null, true))?>">
					</div>
					<div class="form-group col-md-3">
						<label for="input_smtp_port"><?php echo bkntcsaas__('SMTP Port')?>:</label>
						<input class="form-control" id="input_smtp_port" value="<?php echo htmlspecialchars(Helper::getOption('smtp_port', ''))?>">
					</div>
					<div class="form-group col-md-3">
						<label for="input_smtp_secure"><?php echo bkntcsaas__('SMTP Secure')?>:</label>
						<select class="form-control" id="input_smtp_secure">
							<option value="tls"<?php echo (Helper::getOption('smtp_secure', 'tls') == 'tls' ? ' selected' : '')?>><?php echo bkntcsaas__('TLS')?></option>
							<option value="ssl"<?php echo (Helper::getOption('smtp_secure', 'tls') == 'ssl' ? ' selected' : '')?>><?php echo bkntcsaas__('SSL')?></option>
							<option value="no"<?php echo (Helper::getOption('smtp_secure', 'tls') == 'no' ? ' selected' : '')?>><?php echo bkntcsaas__('Disabled ( not recommend )')?></option>
						</select>
					</div>
				</div>

				<div class="form-row">
					<div class="form-group col-md-6">
						<label for="input_smtp_username"><?php echo bkntcsaas__('Username')?>:</label>
						<input class="form-control" id="input_smtp_username" value="<?php echo htmlspecialchars(Helper::getOption('smtp_username', '', null, true))?>">
					</div>
					<div class="form-group col-md-6">
						<label for="input_smtp_password"><?php echo bkntcsaas__('Password')?>:</label>
						<input class="form-control" id="input_smtp_password" value="<?php echo htmlspecialchars(Helper::getOption('smtp_password', '', null, true))?>">
					</div>
				</div>
			</div>

            <div class="gmail_smtp_details dashed-border">
                <div class="form-row">
                    <?php if (! empty($errors)): ?>
                        <?php foreach ($parameters['errors'] as $error): ?>
                            <?php
                                if (isset($error[ 'error_description' ])) {
                                    $msg = htmlspecialchars($error[ 'error_description' ]);
                                } elseif (isset($error['error'][ 'message' ])) {
                                    $msg = htmlspecialchars($error[ 'error' ][ 'message' ]);
                                } else {
                                    $msg = bkntcsaas__('Looks like there was an unknown error caused by Google, please contact support https://support.fs-code.com/');
                                }
                            ?>
                                <div class="form-group col-md-12">
                                    <div style="text-align: center;" class="alert alert-danger" role="alert"><?php echo $msg ?></div>
                                </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <div class="form-group col-md-6">
                        <label for="input_smtp_hostname"><?php echo bkntc__('Client ID')?>:</label>
                        <input class="form-control" id="input_gmail_smtp_client_id" value="<?php echo htmlspecialchars(Helper::getOption('gmail_smtp_client_id', '', null, true))?>">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="input_smtp_port"><?php echo bkntc__('Client secret')?>:</label>
                        <input class="form-control" id="input_gmail_smtp_client_secret" value="<?php echo htmlspecialchars(Helper::getOption('gmail_smtp_client_secret', '', null, true))?>">
                    </div>
                    <div class="form-group col-md-12">
                        <label for="input_smtp_port"><?php echo bkntc__('Redirect URI')?>:</label>
                        <input class="form-control" id="input_redirect_uri" readonly value="<?php echo \BookneticSaaS\Providers\Common\GoogleGmailService::redirectURI(); ?>">
                    </div>
                </div>
                <div class="form-row justify-content-start">
                    <div class="form-group col-md-6 text-left">
                        <button id="gmail_login_btn" class="btn btn-primary px-4 <?php echo $authorized ? 'hidden' : ''?>"><?php echo bkntcsaas__('Authorize') ?></button>
                        <p class="<?php echo $authorized ? '' : 'hidden'?>">
                            <span><?php echo bkntcsaas__('Logged as %s', $email) ?></span>
                            <a id="gmail_logout_btn" href="javascript:void(0)">( <?php echo bkntcsaas__('Log out') ?> )</a>
                        </p>
                    </div>
                </div>

            </div>

            <div class="form-row">
				<div class="form-group col-md-6">
					<label for="input_sender_email"><?php echo bkntcsaas__('Sender E-mail')?>:</label>
					<input class="form-control" id="input_sender_email" value="<?php print htmlspecialchars(Helper::getOption('sender_email', ''))?>">
				</div>
				<div class="form-group col-md-6">
					<label for="input_sender_name"><?php echo bkntcsaas__('Sender Name')?>:</label>
					<input class="form-control" id="input_sender_name" value="<?php print htmlspecialchars(Helper::getOption('sender_name', ''))?>">
				</div>
			</div>

		</div>
	</div>
</div>