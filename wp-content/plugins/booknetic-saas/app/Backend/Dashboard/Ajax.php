<?php

namespace BookneticSaaS\Backend\Dashboard;

use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Helpers\NotificationHelper;
use BookneticApp\Providers\Core\Controller as SaaSController;

class Ajax extends SaaSController
{
    public function dismiss_notification()
    {
        $slug = Helper::_post('slug', '', 'string');

        if (empty($slug)) {
            return $this->response(true);
        }

        $notifications = NotificationHelper::getAll();

        if (empty($notifications) || empty($notifications[ $slug ])) {
            return $this->response(true);
        }

        $notifications[ $slug ][ 'visible' ] = false;

        NotificationHelper::save($notifications);

        return $this->response(true);
    }
}
