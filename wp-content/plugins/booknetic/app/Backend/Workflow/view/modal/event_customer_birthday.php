<?php

use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Helpers\Date;

defined('ABSPATH') or die();

/**
 * @var mixed $parameters
 */
?>
<script type="application/javascript"
        src="<?php echo Helper::assets('js/event_customer_birthday.js', 'workflow') ?>"></script>

<div class="fs-modal-title">
    <div class="title-text"><?php echo bkntc__('Edit event settings') ?></div>
    <div class="close-btn" data-dismiss="modal"><i class="fa fa-times"></i></div>;
</div>

<div class="fs-modal-body">
    <div class="fs-modal-body-inner">

        <div class="form-row">
            <div class="form-group col-md-12">
                <label for="input_locations"><?php echo bkntc__('Offset') ?></label>
                <div class="input-group">
                    <select class="form-control" id="input_offset_sign">
                        <option value="before" <?php echo $parameters['offset_sign'] === 'before' ? 'selected' : '' ?>><?php echo bkntc__('Before') ?></option>
                        <option value="after" <?php echo $parameters['offset_sign'] === 'after' ? 'selected' : '' ?>><?php echo bkntc__('After') ?></option>
                    </select>
                    <input type="number" min="0" class="form-control" value="<?php echo $parameters['offset_value'] ?>"
                           id="input_offset_value">
                    <label for="input_offset_type"></label>
                    <p class="form-control-plaintext ml-2" style="margin-bottom:0;">
                        <?php echo bkntc__('Day(s)') ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group col-md-4">
                <label for="input_time"><?php echo bkntc__('Time') ?> <span class="required-star">*</span></label>
                <select class="form-control" id="input_time" name="time">
                    <?php
                    $selectedTime = $parameters['selected_time'] ?? '08:00';

for ($h = 0; $h < 24; $h++) {
    for ($m = 0; $m < 60; $m += 5) {
        $time = sprintf('%02d:%02d', $h, $m);
        $selected = ($time === $selectedTime) ? 'selected' : '';
        echo "<option value=\"$time\" $selected>$time</option>";
    }
}
?>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group col-md-12">
                <label for="input_gender"><?php echo bkntc__('Gender') ?></label>
                <select class="form-control" id="input_gender" name="gender">
                    <option value="-" <?php echo $parameters['gender'] === '-' ? 'selected' : ''?>>
                        <?php echo bkntc__('All Gender') ?>
                    </option>
                    <option value="male" <?php echo $parameters['gender'] === 'male' ? 'selected' : ''?>>
                        <?php echo bkntc__('Male') ?>
                    </option>
                    <option value="female" <?php echo $parameters['gender'] === 'female' ? 'selected' : ''?>>
                        <?php echo bkntc__('Female') ?>
                    </option>
                    <option value="not_specified" <?php echo $parameters['gender'] === 'not_specified' ? 'selected' : ''  ?>>
                        <?php echo bkntc__('Not specified') ?>
                    </option>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group col-md-12">
                <label for="input_years"><?php echo bkntc__('Year filter') ?></label>
                <select class="form-control" id="input_years" name="years[]" multiple>
                    <?php
$currentYear = Date::format('Y');
$startYear = $currentYear - 100;
$selectedYears = $parameters['years'] ?? [];
echo '<option value="-">' . bkntc__('All Years') . '</option>';

for ($year = $currentYear; $year >= $startYear; $year--) {
    $selected = in_array($year, $selectedYears) ? 'selected' : '';
    echo '<option value="' . $year . '" ' . $selected . '>' . $year . '</option>';
}
?>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group col-md-12">
                <label for="input_months"><?php echo bkntc__('Month filter') ?></label>
                <select class="form-control" id="input_months" name="months[]" multiple>
                    <?php
    $months = [
        0 => bkntc__('All months'),
        1 => bkntc__('January'),
        2 => bkntc__('February'),
        3 => bkntc__('March'),
        4 => bkntc__('April'),
        5 => bkntc__('May'),
        6 => bkntc__('June'),
        7 => bkntc__('July'),
        8 => bkntc__('August'),
        9 => bkntc__('September'),
        10 => bkntc__('October'),
        11 => bkntc__('November'),
        12 => bkntc__('December'),
    ];

$selectedMonths = $parameters['month'] ?? [];

foreach ($months as $num => $name) {
    $selected =  in_array($num, $selectedMonths) ? 'selected' : '';
    echo '<option value="' . $num . '" ' . $selected . '>' . htmlspecialchars($name) . '</option>';
}
?>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group col-md-12">
                <label for="input_categories"><?php echo bkntc__('Category filter')?></label>
                <select class="form-control" id="input_categories" multiple>
                    <?php
                    foreach ($parameters['categories'] as $category) {
                        echo '<option value="' . (int)$category['id'] . '" selected>' . htmlspecialchars($category['name']) . '</option>';
                    }
?>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group col-md-12">
                <label for="input_locale"><?php print bkntc__('Locale filter'); ?></label>

                <select class="form-control" name="locale" id="input_locale">
                    <?php foreach ($parameters[ 'locales' ] as $lang): ?>
                        <option value="<?php echo htmlspecialchars($lang[ 'language' ]); ?>" lang="<?php echo htmlspecialchars(current($lang[ 'iso' ])); ?>" <?php echo $parameters[ 'locale' ] == $lang[ 'language' ] ? 'selected' : ''; ?>><?php echo htmlspecialchars($lang[ 'native_name' ]); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

    </div>
</div>

<div class="fs-modal-footer">
    <button type="button" class="btn btn-lg btn-default" data-dismiss="modal"><?php echo bkntc__('CANCEL') ?></button>
    <button type="button" class="btn btn-lg btn-primary" id="eventSettingsSave"><?php echo bkntc__('SAVE') ?></button>
</div>