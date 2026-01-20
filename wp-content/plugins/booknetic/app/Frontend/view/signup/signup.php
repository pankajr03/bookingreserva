<?php

defined('ABSPATH') or die();

use BookneticApp\Providers\Helpers\Helper;

?>

<div class="booknetic_signup">
    <div class="booknetic_step_1">
        <div class="booknetic_header"><?php echo bkntc__('Sign Up')?></div>
        <form method="post" class="booknetic_form">
            <div class="booknetic_form_element" style="display:flex; justify-content: space-between">
                <div class="booknetic_customer_info" style="">
                    <label for="booknetic_first_name"><?php echo bkntc__('First name')?></label>
                    <input type="text" id="booknetic_first_name" maxlength="100" name="name">
                </div>
                <div class="booknetic_customer_info" style="">
                    <label for="booknetic_last_name"><?php echo bkntc__('Last name')?></label>
                    <input type="text" id="booknetic_last_name" maxlength="100" name="name">
                </div>
            </div>
            <div class="booknetic_form_element">
                <label for="booknetic_email"><?php echo bkntc__('Email')?></label>
                <input type="text" id="booknetic_email" maxlength="100" name="email">
            </div>
            <div class="booknetic_form_element">
                <label for="booknetic_password"><?php echo bkntc__('Password')?></label>
                <div class="bkntc-password-input">
                    <input type="password" id="booknetic_password" name="password">
                    <button type="button" class="bkntc-toggle-password-visibility">
                        <span class="bkntc-eye-icon bkntc-eye-open">
                            <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path d="M320 96C239.2 96 174.5 132.8 127.4 176.6C80.6 220.1 49.3 272 34.4 307.7C31.1 315.6 31.1 324.4 34.4 332.3C49.3 368 80.6 420 127.4 463.4C174.5 507.1 239.2 544 320 544C400.8 544 465.5 507.2 512.6 463.4C559.4 419.9 590.7 368 605.6 332.3C608.9 324.4 608.9 315.6 605.6 307.7C590.7 272 559.4 220 512.6 176.6C465.5 132.9 400.8 96 320 96zM176 320C176 240.5 240.5 176 320 176C399.5 176 464 240.5 464 320C464 399.5 399.5 464 320 464C240.5 464 176 399.5 176 320zM320 256C320 291.3 291.3 320 256 320C244.5 320 233.7 317 224.3 311.6C223.3 322.5 224.2 333.7 227.2 344.8C240.9 396 293.6 426.4 344.8 412.7C396 399 426.4 346.3 412.7 295.1C400.5 249.4 357.2 220.3 311.6 224.3C316.9 233.6 320 244.4 320 256z"/></svg>
                        </span>
                        <span class="bkntc-eye-icon bkntc-eye-closed" style="display: none">
                           <svg width="20"  height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path d="M73 39.1C63.6 29.7 48.4 29.7 39.1 39.1C29.8 48.5 29.7 63.7 39 73.1L567 601.1C576.4 610.5 591.6 610.5 600.9 601.1C610.2 591.7 610.3 576.5 600.9 567.2L504.5 470.8C507.2 468.4 509.9 466 512.5 463.6C559.3 420.1 590.6 368.2 605.5 332.5C608.8 324.6 608.8 315.8 605.5 307.9C590.6 272.2 559.3 220.2 512.5 176.8C465.4 133.1 400.7 96.2 319.9 96.2C263.1 96.2 214.3 114.4 173.9 140.4L73 39.1zM236.5 202.7C260 185.9 288.9 176 320 176C399.5 176 464 240.5 464 320C464 351.1 454.1 379.9 437.3 403.5L402.6 368.8C415.3 347.4 419.6 321.1 412.7 295.1C399 243.9 346.3 213.5 295.1 227.2C286.5 229.5 278.4 232.9 271.1 237.2L236.4 202.5zM357.3 459.1C345.4 462.3 332.9 464 320 464C240.5 464 176 399.5 176 320C176 307.1 177.7 294.6 180.9 282.7L101.4 203.2C68.8 240 46.4 279 34.5 307.7C31.2 315.6 31.2 324.4 34.5 332.3C49.4 368 80.7 420 127.5 463.4C174.6 507.1 239.3 544 320.1 544C357.4 544 391.3 536.1 421.6 523.4L357.4 459.2z"/></svg>
                        </span>
                    </button>
                </div>
            </div>
            <div>
                <button type="submit" class="booknetic_btn_primary booknetic_signup_btn"><?php echo bkntc__('CONTINUE')?></button>
            </div>
        </form>
        <div class="booknetic_footer">
            <span><?php echo bkntc__('Already have an account?')?></span>
            <a href="<?php echo get_permalink(Helper::getOption('regular_sing_in_page'))?>"><?php echo bkntc__('Sign in')?></a>
        </div>
    </div>
    <div class="booknetic_step_2">
        <div class="booknetic_header"><?php echo bkntc__('Congratulations!')?></div>
        <div class="booknetic_check_your_email">
            <?php echo bkntc__('We need to verify your email.')?><br/>
            <?php echo bkntc__('Please, check your inbox for a confirmation link.')?>
        </div>
        <div class="booknetic_email_success">
            <img src="<?php echo Helper::assets('images/signup-success.svg', 'front-end')?>" alt="">
        </div>
        <div class="booknetic_footer booknetic_resend_activation">
            <span><?php echo bkntc__('Didn\'t receive the email?')?></span>
            <a href="javascript:;" class="booknetic_resend_activation_link"><?php echo bkntc__('Resend again')?></a>
        </div>
    </div>
</div>