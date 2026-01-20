<?php

defined('ABSPATH') or die();

use BookneticApp\Providers\Helpers\Helper;

?>

<link rel="stylesheet" href="<?php echo Helper::assets('css/holidays.css', 'Settings')?>">
<script type="application/javascript" src="<?php echo Helper::assets('js/holidays.js', 'Settings')?>"></script>

<div id="holiday-settings" class="yearly_calendar"></div>

<script>
    var dbHolidays = <?php echo $parameters['holidays']?>;
</script>
