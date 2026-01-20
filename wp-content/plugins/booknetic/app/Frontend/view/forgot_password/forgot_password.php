<?php

defined('ABSPATH') or die();

use BookneticApp\Providers\Helpers\Helper;

?>

<div class="booknetic_forgot_password">
    <div class="booknetic_step_1">
        <div class="booknetic_header"><?php echo bkntc__('Forgot Password')?></div>
        <form method="post" class="booknetic_form">
            <div class="booknetic_form_element">
                <label for="booknetic_email"><?php echo bkntc__('Email')?></label>
                <input type="text" id="booknetic_email" maxlength="100" name="email">
            </div>
            <div>
                <button type="submit" class="booknetic_btn_primary booknetic_forgot_password_btn"><?php echo bkntc__('CONTINUE')?></button>
            </div>
        </form>
   <div class="booknetic_footer">
    <a href="<?php echo get_permalink(Helper::getOption('regular_sing_in_page'))?>"><?php echo bkntc__('Go Back')?></a>
</div>

    </div>
    <div class="booknetic_step_2">
        <div class="booknetic_header"><?php echo bkntc__('Password Reset')?></div>
        <div class="booknetic_check_your_email">
            <?php echo bkntc__("To keep your account safe, we just need to confirm it's you.")?><br/>
            <?php echo bkntc__('Check your inbox for a password reset link.')?>
        </div>
        <div class="booknetic_email_success">
            <img src="<?php echo Helper::assets('images/forgot-password.svg', 'front-end')?>" alt="<?php echo bkntc__('Email Sent')?>">
        </div>
        <div class="booknetic_footer booknetic_resend_activation">
            <span><?php echo bkntc__('Didn\'t receive the email?')?></span>
            <a href="javascript:;" class="booknetic_resend_activation_link"><?php echo bkntc__('Resend again')?></a>
        </div>
    </div>
</div>
