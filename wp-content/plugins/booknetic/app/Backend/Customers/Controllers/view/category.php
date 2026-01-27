<?php

defined('ABSPATH') or die();

use BookneticApp\Providers\Helpers\Helper;

/**
 * @var mixed $parameters
 */
echo $parameters['table'];
?>
<link rel="stylesheet" type="text/css" href="<?php echo Helper::assets('css/bootstrap-colorpicker.min.css', "Customers") ?>"/>
<script type="application/javascript" src="<?php echo Helper::assets('js/bootstrap-colorpicker.min.js', "Customers") ?>"></script>
<script type="application/javascript" src="<?php echo Helper::assets('js/customer_category.js', "Customers") ?>"></script>
