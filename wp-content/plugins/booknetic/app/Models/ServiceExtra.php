<?php

namespace BookneticApp\Models;

use BookneticApp\Providers\DB\Model;
use BookneticApp\Providers\DB\MultiTenant;
use BookneticApp\Providers\Translation\Translator;

/**
 * @property-read int $id
 * @property-read int $service_id
 */
class ServiceExtra extends Model
{
    use MultiTenant;
    use Translator;

    protected static $translations = [ 'name', 'notes' ];
}
