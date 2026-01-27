<?php

defined('ABSPATH') or die();

use BookneticSaaS\Providers\Helpers\Helper;

/**
 * @var array $options
*/
?>

<div id="booknetic_loading" class="hidden"><div><?php echo bkntcsaas__('Installing...')?></div><div><?php echo bkntcsaas__('( it can take some time, please wait... )')?></div></div>

<div id="booknetic_alert" class="hidden"></div>

<div class="booknetic_area">
	<div class="booknetic_install_panel">
		<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('booknetic_install')?>">
		<div class="booknetic_logo_d">
            <img src="<?php echo Helper::assets('images/logo-black.svg')?>" alt="">
            <span>SaaS</span>
        </div>
		<div class="booknetic_input_d"><label for="booknetic_install_purchase_code"></label><input type="text" placeholder="<?php echo bkntcsaas__('License code')?>" name="purchase_code" id="booknetic_install_purchase_code"></div>
        <div class="booknetic_input_d">
            <label for="booknetic_install_email"></label><input type="text" placeholder="<?php echo bkntcsaas__('Email')?>" name="email" id="booknetic_install_email">
        </div>
		<div class="booknetic_input_d">
            <label for="booknetic_install_found_from"></label><select type="text" name="where_find" id="booknetic_install_found_from">
				<option disabled selected><?php echo bkntcsaas__('Where did You find us?')?></option>
				<?php
                foreach ($options as $value => $option) {
                    echo '<option value="' . htmlspecialchars($value) . '">' . htmlspecialchars($option) . '</option>';
                }
?>
			</select>
		</div>
        <div>
            <div class="booknetic_input_d" style="max-width: 320px;">
                <input type="checkbox" id="booknetic_install_subscribed_to_newsletter">&emsp;<label for="booknetic_install_subscribed_to_newsletter">Stay connected for exciting features, updates, and limited-time offers!</label>
            </div>
        </div>
		<div class="booknetic_submit_d"><button type="button" id="booknetic_install_btn"><?php echo bkntcsaas__('INSTALL')?></button></div>
		<div class="booknetic_help_text"><?php echo bkntcsaas__('Install process can take 30-60 sec., please wait...')?></div>
	</div>
</div>