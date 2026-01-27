<?php

defined('ABSPATH') or die();

use BookneticSaaS\Providers\Helpers\Helper;

?>

<div class="bookneticsaas_forgot_password">
	<div class="bookneticsaas_step_1">
		<div class="bookneticsaas_header"><?php echo bkntcsaas__('Forgot Password')?></div>
		<form method="post" class="bookneticsaas_form">
			<div class="bookneticsaas_form_element">
				<label for="bookneticsaas_email"><?php echo bkntcsaas__('Email')?></label>
				<input type="text" id="bookneticsaas_email" maxlength="100" name="email">
			</div>
			<div>
				<button type="submit" class="bookneticsaas_btn_primary bookneticsaas_forgot_password_btn"><?php echo bkntcsaas__('CONTINUE')?></button>
			</div>
		</form>
		<div class="bookneticsaas_footer">
			<span><?php echo bkntcsaas__('Already have an account?')?></span>
			<a href="<?php echo get_permalink(Helper::getOption('sign_in_page'))?>"><?php echo bkntcsaas__('Sign in')?></a>
		</div>
	</div>
	<div class="bookneticsaas_step_2">
		<div class="bookneticsaas_header"><?php echo bkntcsaas__('Password Reset')?></div>
		<div class="bookneticsaas_check_your_email">
			<?php echo bkntcsaas__("To keep your account safe, we just need to confirm it's you.")?><br/>
			<?php echo bkntcsaas__('Check your inbox for a password reset link.')?>
		</div>
		<div class="bookneticsaas_email_success">
            <img src="<?php echo Helper::assets('images/forgot-password.svg', 'front-end')?>" alt="<?php echo bkntc__('Email Sent')?>">
		</div>
		<div class="bookneticsaas_footer bookneticsaas_resend_activation">
			<span><?php echo bkntcsaas__('Didn\'t receive the email?')?></span>
			<a href="javascript:;" class="bookneticsaas_resend_activation_link"><?php echo bkntcsaas__('Resend again')?></a>
		</div>
	</div>
</div>
