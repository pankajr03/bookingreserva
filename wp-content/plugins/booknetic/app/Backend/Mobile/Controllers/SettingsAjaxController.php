<?php

namespace BookneticApp\Backend\Mobile\Controllers;

use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Core\Controller;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Request\Post;

class SettingsAjaxController extends Controller
{
    public function save()
    {
        $allowStaffToRegenerate = Post::bool('allow_staff_to_regenerate_app_password');

        Capabilities::must('mobile_app_save_settings');

        Helper::setOption('mobile_app_allow_staff_to_regenerate_app_password', $allowStaffToRegenerate);

        return $this->response([
            'status' => 'success',
            'message' => 'Settings saved successfully.'
        ]);
    }
}
