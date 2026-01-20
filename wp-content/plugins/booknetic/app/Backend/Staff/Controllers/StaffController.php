<?php

namespace BookneticApp\Backend\Staff\Controllers;

use BookneticApp\Backend\Staff\Exceptions\StaffException;
use BookneticApp\Backend\Staff\Services\StaffService;
use BookneticApp\Models\Staff;
use BookneticApp\Providers\Request\Post;
use BookneticApp\Providers\UI\Abstracts\AbstractDataTableUI;
use BookneticApp\Providers\UI\DataTableUI;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Core\Permission;

class StaffController extends \BookneticApp\Providers\Core\Controller
{
    private StaffService $staffService;

    public function __construct()
    {
        $this->staffService = new StaffService();
    }
    public function index()
    {
        Capabilities::must('staff');

        $dataTable = new DataTableUI(new Staff());

        $dataTable->addAction('enable', bkntc__('Enable'), function ($ids) {
            Staff::where('id', 'in', $ids)->update([ 'is_active' => 1 ]);
        }, AbstractDataTableUI::ACTION_FLAG_BULK);
        $dataTable->addAction('disable', bkntc__('Disable'), function ($ids) {
            Staff::where('id', 'in', $ids)->update([ 'is_active' => 0 ]);
        }, AbstractDataTableUI::ACTION_FLAG_BULK);

        $dataTable->addAction('edit', bkntc__('Edit'));

        if (Permission::isAdministrator() || Capabilities::userCan('staff_delete')) {
            $dataTable->addAction(
                'delete',
                bkntc__('Delete'),
                [ $this, 'delete_staff' ],
                AbstractDataTableUI::ACTION_FLAG_SINGLE | AbstractDataTableUI::ACTION_FLAG_BULK
            );
        }

        $dataTable->addAction('share', bkntc__('Share'));

        $dataTable->setTitle(bkntc__('Staff'));

        if (Permission::isAdministrator() || Capabilities::userCan('staff_add')) {
            $dataTable->addNewBtn(bkntc__('ADD STAFF'));
        }

        $dataTable->searchBy([ "name", 'email', 'phone_number' ]);

        $dataTable->addColumns(bkntc__('ID'), 'id');
        $dataTable->addColumns(
            bkntc__('STAFF NAME'),
            fn ($staff) => Helper::profileCard($staff[ 'name' ], $staff[ 'profile_image' ], '', 'staff'),
            [ 'is_html' => true, 'order_by_field' => "name" ]
        );
        $dataTable->addColumns(bkntc__('EMAIL'), 'email');
        $dataTable->addColumns(bkntc__('PHONE'), 'phone_number');

        $table = $dataTable->renderHTML();

        $edit = Helper::_get('edit', '0', 'int');

        add_filter('bkntc_localization', function ($localization) {
            $localization[ 'delete_associated_wordpress_account' ] = bkntc__('Delete associated WordPress account');
            $localization[ 'link_copied' ]                         = bkntc__('Link copied!');

            return $localization;
        });

        $this->view('index', [
            'table' => $table,
            'edit'  => $edit
        ]);
    }

    public function delete_staff()
    {
        try {
            $ids = Post::array('ids');
            $deleteWpUser = (bool) Post::int('delete_wp_user', 1);
            $result = $this->staffService->delete($ids, $deleteWpUser);

            return $this->response($result);
        } catch (StaffException $e) {
            return $this->response(false, $e->getMessage());
        } catch (\Throwable $e) {
            return $this->response(false, ['message' => bkntc__('Unexpected server error.')]);
        }
    }
}
