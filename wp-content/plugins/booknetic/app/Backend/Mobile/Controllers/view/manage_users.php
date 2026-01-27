<?php

defined('ABSPATH') or die();

use BookneticApp\Providers\Helpers\Helper;

/**
 * @var array $parameters
 */
$users = $parameters['users'];
$availableSeats = $parameters['availableSeats'];

?>

<link rel="stylesheet" href="<?php echo Helper::assets('css/manage-users.css', 'Mobile') ?>" type="text/css">
<link rel="stylesheet" href="<?php echo Helper::assets('css/password-generate-modal.css', 'Mobile') ?>" type="text/css">
<link rel="stylesheet" href="<?php echo Helper::assets('css/add-member-modal.css', 'Mobile') ?>" type="text/css">

<script type="application/javascript" src="<?php echo Helper::assets('js/manage_users.js', 'Mobile') ?>"></script>

<div class="member-dropdown position-absolute" data-seat-id="" data-username="">
    <button class="d-flex align-items-center regenerate-password-btn">
        <img src="<?php echo Helper::assets('images/password-regenerate-icon.svg', 'Mobile') ?>" alt="">
        <span><?php echo bkntc__('Regenerate App Password') ?></span>
    </button>
    <button class="d-flex align-items-center unassign-btn">
        <img src="<?php echo Helper::assets('images/unassign-icon.svg', 'Mobile') ?>" alt="">
        <span><?php echo bkntc__('Unassign') ?></span>
    </button>
</div>

<div class="manage-seats-table">
	<div class="table-header d-flex align-items-center justify-content-between">
		<div>
			<p class="m-0 p-0"><?php echo bkntc__('Members') ?></p>
		</div>
		<div class="ml-auto d-flex align-items-center">
			<p class="m-0 p-0"><?php echo bkntc__('Total seats:') ?></p>
			<span><?php echo $availableSeats + count($users) ?></span>
		</div>
		<div class="column-break"></div>
		<div class="d-flex align-items-center">
			<p class="m-0 p-0"><?php echo bkntc__('Assigned seats:') ?></p>
			<span><?php echo count($users) ?></span>
		</div>
		<div class="column-break"></div>
		<div class="d-flex align-items-center">
			<p class="m-0 p-0"><?php echo bkntc__('Free seats:') ?></p>
			<span><?php echo $availableSeats ?></span>
		</div>
		<div>
			<button class="btn-primary btn-sm add-members-btn"><?php echo bkntc__('Add members') ?></button>
		</div>
	</div>
	<div class="table-body">
        <?php if (count($users) === 0) : ?>
            <div class="empty-info text-center">
                <h2 class="m-0 p-0"><?php echo bkntc__('No members found') ?></h2>
                <p class="p-0"><?php echo bkntc__('You haven’t added anyone yet.') ?></p>
            </div>
        <?php else: ?>
            <?php foreach ($users as $user) : ?>
                <div data-seat-id="<?php echo $user['seatId'] ?>" data-username = "<?php echo $user['username'] ?>" class="member d-flex align-items-center justify-content-between">
                    <div class="member-user-info d-flex align-items-center">
                        <img src="<?php echo $user['image'] ?>"
                             alt="Name avatar">
                        <div class="d-flex flex-column">
                            <p class="m-0 p-0"><?php echo $user['full_name'] ?></p>
                            <span><?php echo $user['username'] ?></span>
                        </div>
                    </div>
                    <div class="member-user-device">
                        <div class="d-flex align-items-center">
                            <p class="device-name p-0 m-0">
                                <?php $loggedInDevice = $user['loggedInDevice'] ?? null;
                if (empty($loggedInDevice)):?>
                                            <?php echo bkntc__('No Device')?>
                                         <?php else:?>
                                    <?php
                                     $brand = $loggedInDevice['brand'] ?? '';
                                             $model = $loggedInDevice['modelName'] ?? '';
                                             $label = trim($brand . ' ' . $model);
                                             echo htmlspecialchars($label !== '' ? $label : bkntc__('Unknown device'));
                                             ?>
                                <?php endif ;?>
                            </p>
                            <?php if ($user['isLoggedIn'] !== false):?>
                                <a href="#" class="log-out-btn"><?php echo bkntc__('Log out'); ?></a>
                            <?php endif ;?>
                        </div>
                        <span class="status"><?php echo bkntc__('Logged in device') ?></span>
                    </div>
                    <div class="member-status d-flex align-items-center position-relative">
                        <div class="d-flex align-items-center">
                            <?php if (!empty($user['isDisabledOnRenewal'])): ?>
                                <i class="fa fa-exclamation-triangle help-icon do_tooltip p-0" data-content="<?php echo bkntc__('You will lose this seat at the start of your next billing period.'); ?>" data-original-title="" title=""></i>
                            <?php endif; ?>
                            <?php if ($user['isLoggedIn'] === false) : ?>
                                <span class="status-badge not-logged-in"><?php echo bkntc__('Not logged in') ?></span>
                            <?php else : ?>
                                <span class="status-badge logged-in"><?php echo bkntc__('Logged in') ?></span>
                            <?php endif ; ?>
                        </div>
                        <i class="fa fa-ellipsis-v member-dropdown-btn cursor-pointer"></i>
                    </div>
                </div>
            <?php endforeach?>
        <?php endif;?>
	</div>
</div>

<div class="booknetic-modal regenerate-password-modal d-none">
	<div class="modal-header d-flex align-items-center justify-content-between">
		<h3 class="m-0 modal-title"><?php echo bkntc__('Regenerate App Password') ?></h3>
		<button class="modal-close-btn d-flex align-items-center justify-content-center">
			<img src="<?php echo Helper::assets('images/x-close.svg', 'Mobile')?>" alt="">
		</button>
	</div>
	
	<div class="modal-body">
		<p class="modal-text"><?php echo bkntc__('Regenerating your App Password will automatically delete the old one. Any devices currently logged in with the old password will be logged out immediately.') ?></p>
	</div>
	
	<div class="modal-footer d-flex justify-content-end">
		<button class="modal-cancel button btn-outline-secondary m-0"><?php echo bkntc__('Cancel') ?></button>
		<button class="modal-confirm button btn-primary m-0"><?php echo bkntc__('Regenerate') ?></button>
	</div>
</div>

<div class="booknetic-modal unassign-modal d-none">
	<div class="modal-header d-flex align-items-center justify-content-between">
		<h3 class="m-0 modal-title"><?php echo bkntc__('Unassign') ?></h3>
		<button class="modal-close-btn d-flex align-items-center justify-content-center">
			<img src="<?php echo Helper::assets('images/x-close.svg', 'Mobile')?>" alt="">
		</button>
	</div>
	
	<div class="modal-body">
		<p class="modal-text"><?php echo bkntc__('Unassigned users are logged out of the application and the seat count is reset.') ?></p>
	</div>
	
	<div class="modal-footer d-flex justify-content-end">
		<button class="modal-cancel button btn-outline-secondary m-0"><?php echo bkntc__('Cancel') ?></button>
		<button class="modal-confirm button btn-primary m-0"><?php echo bkntc__('Unassign') ?></button>
	</div>
</div>

<div class="booknetic-modal log-out-modal d-none">
	<div class="modal-header d-flex align-items-center justify-content-between">
		<h3 class="m-0 modal-title"><?php echo bkntc__('Are you sure?') ?></h3>
		<button class="modal-close-btn d-flex align-items-center justify-content-center">
			<img src="<?php echo Helper::assets('images/x-close.svg', 'Mobile')?>" alt="">
		</button>
	</div>
	
	<div class="modal-body">
		<p class="modal-text"><?php echo bkntc__('User has to be sign-in in order to use mobile app') ?></p>
	</div>
	
	<div class="modal-footer d-flex justify-content-end">
		<button class="modal-cancel button btn-outline-secondary m-0"><?php echo bkntc__('Cancel') ?></button>
		<button class="modal-confirm button btn-primary m-0"><?php echo bkntc__('Logout') ?></button>
	</div>
</div>

<div class="booknetic-modal seat-credentials-modal d-none">
	<div class="modal-header d-flex align-items-center justify-content-between">
		<h3 class="m-0 modal-title"><?php echo bkntc__('App Password') ?></h3>
		<button class="modal-close-btn d-flex align-items-center justify-content-center">
			<img src="<?php echo Helper::assets('images/x-close.svg', 'Mobile')?>" alt="">
		</button>
	</div>
	
	<div class="modal-body password-generate-modal">
		<div class="success-badge"><?php echo bkntc__('App password was successfully regenerated') ?></div>
		<div class="d-flex align-items-center justify-content seat-credentials-container">
			<div class="seat-credentials d-flex flex-column">
                <div class="website">
                    <p class="p-0"><?php echo bkntc__('Website URL') ?></p>
                    <div class="credential-input website-url"><?php echo site_url();?></div>
                </div>
				<div class="username">
					<p class="p-0"><?php echo bkntc__('Username') ?></p>
					<div class="credential-input username-credential">@username</div>
				</div>
				<div class="password">
					<p class="p-0"><?php echo bkntc__('Password') ?></p>
					<div class="credential-input d-flex align-items-center justify-content-between position-relative">
						<span class="password-credential">sSDsd12d</span>
						<div class="d-flex align-items-center justify-content-between copy-btn">
							<img src="<?php echo Helper::assets('icons/copy-icon.svg')?>" alt="">
							<span><?php echo bkntc__('Copy')?></span>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	
	<div class="modal-footer d-flex justify-content-end">
		<button class="modal-cancel button btn-outline-secondary m-0"><?php echo bkntc__('Close') ?></button>
	</div>
</div>

<div class="booknetic-modal no-user-available d-none">
	<div class="modal-header d-flex align-items-center justify-content-between">
		<h3 class="m-0 modal-title"><?php echo bkntc__('Add user') ?></h3>
		<button class="modal-close-btn d-flex align-items-center justify-content-center">
			<img src="<?php echo Helper::assets('images/x-close.svg', 'Mobile')?>" alt="">
		</button>
	</div>
	
	<div class="modal-body">
		<img src="<?php echo Helper::assets('images/add-members.svg', 'Mobile')?>" alt="">
		<p class="m-0 p-0 no-plan-text"><?php echo bkntc__('You currently have no free seats available. To add a new user, please purchase additional seats for your account first.')?></p>
		<button class="modal-confirm button btn-primary m-0 d-flex align-items-center manage-seats-btn">
			<img src="<?php echo Helper::assets('images/add-user.svg', 'Mobile')?>" alt="">
			<span class="no-plan-btn-text"><?php echo bkntc__('Add user')?></span>
		</button>
	</div>
</div>

<div class="booknetic-modal add-users-modal d-none">
	<div class="modal-header d-flex align-items-center justify-content-between">
		<h3 class="m-0 modal-title"><?php echo bkntc__('Add user') ?></h3>
		<button class="modal-close-btn d-flex align-items-center justify-content-center">
			<img src="<?php echo Helper::assets('images/x-close.svg', 'Mobile')?>" alt="">
		</button>
	</div>
	
	<div class="modal-body">
		<label for="select-user"><?php echo bkntc__('Select a user')?></label>
		<select class="form-control" id="select-user">
			<option value=""></option>
		</select>
	</div>
	
	<div class="modal-footer d-flex justify-content-end">
		<button class="modal-cancel button btn-outline-secondary m-0"><?php echo bkntc__('Cancel') ?></button>
		<button class="modal-confirm assign-user-confirm button btn-primary m-0"><?php echo bkntc__('Add') ?></button>
	</div>
</div>
