<?php

defined('ABSPATH') or die();

use BookneticApp\Providers\Helpers\Helper;

?>

<link rel="stylesheet" href="<?php echo Helper::assets('css/general_settings.css', 'Settings')?>">
<script type="application/javascript" src="<?php echo Helper::assets('js/advanced_settings.js', 'Settings')?>"></script>

<form id="advanced-settings" class="position-relative">
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="input_flexible_timeslot"><?php echo bkntc__('Flexible Timeslots')?>:</label>
                    <select class="form-control" id="input_flexible_timeslot">
                        <option value="0"<?php echo $parameters['flexibleTimeslot'] == '0' ? ' selected' : ''?>><?php echo bkntc__('Disabled')?></option>
                        <option value="1"<?php echo $parameters['flexibleTimeslot'] == '1' ? ' selected' : ''?>><?php echo bkntc__('Enabled')?></option>
                    </select>
                </div>

            <div class="form-group col-md-6">
                <label for="time_priority"><?php echo bkntc__('Time Priority')?>:</label>
                <select class="form-control" id="time_priority">
                    <option value="staff"<?php echo $parameters['priority'] == 'staff' ? ' selected' : ''?>><?php echo bkntc__('Staff')?></option>
                    <option value="service"<?php echo $parameters['priority'] == 'service' ? ' selected' : ''?>><?php echo bkntc__('Service')?></option>
                </select>
            </div>
        </form>
