<?php
defined('ABSPATH') or die();

use BookneticApp\Providers\Helpers\Helper;

/**
 * @var mixed $parameters
 */
?>

<?php echo $parameters['table']; ?>

<link rel="stylesheet" type="text/css" href="<?php echo Helper::assets('css/service-list.css', 'Services')?>" />
<script src="<?php echo Helper::assets('js/add_category.js', 'Services')?>"></script>
