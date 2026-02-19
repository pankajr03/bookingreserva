<?php

namespace BookneticApp\Models;

use BookneticApp\Providers\DB\Model;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $user_id
 * @property string $type
 * @property string $title
 * @property string $message
 * @property string $action_type
 * @property string $action_data
 * @property string $read_at
 * @property string $created_at
 * @property string $updated_at
 */
class Notification extends Model
{
    protected static bool $timeStamps = true;
}
