<?php

defined('ABSPATH') or die();

use BookneticApp\Providers\Helpers\Helper;

?>

<link rel="stylesheet" href="<?php echo Helper::assets('css/backup_settings.css', 'Settings')?>">
<script type="application/javascript" src="<?php echo Helper::assets('js/backup_settings.js', 'Settings')?>"></script>

<div class="row mb-4 mt-3" id="backup-settings">
    <div class="col-md-4">
        <input type="file" class="form-control" accept=".Booknetic" id="file_to_restore">
        <div class="form-control" data-label="<?php echo bkntc__('BROWSE')?>"><?php echo bkntc__('Select file to restore')?></div>
    </div>
    <div class="col-md-3">
        <button type="button" class="btn btn-lg btn-primary" id="import_data_btn"><?php echo bkntc__('IMPORT DATA')?></button>
    </div>
    <div class="col-md-3">
        <button type="button" class="btn btn-lg btn-success float-right" id="export_data_btn"><i class="fa fa-cloud-download-alt pr-2"></i> <?php echo bkntc__('EXPORT DATA')?></button>
    </div>
</div>
