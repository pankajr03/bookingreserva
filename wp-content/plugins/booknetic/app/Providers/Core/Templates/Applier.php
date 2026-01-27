<?php

namespace BookneticApp\Providers\Core\Templates;

use BookneticApp\Models\Appearance;
use BookneticApp\Models\Location;
use BookneticApp\Models\Service;
use BookneticApp\Models\ServiceCategory;
use BookneticApp\Models\ServiceStaff;
use BookneticApp\Models\Staff;
use BookneticApp\Models\Timesheet;
use BookneticApp\Models\Workflow;
use BookneticApp\Models\WorkflowAction;
use BookneticApp\Providers\DB\Collection;
use BookneticApp\Providers\FSCode\Clients\FSCodeAPIClient;
use BookneticApp\Providers\Core\Permission;
use BookneticApp\Providers\Helpers\Helper;

class Applier
{
    use Data;
    use ApplierCreate;

    private static int $reservedTenantId = 0;

    private bool $fromServer;

    private array $newIds = [
        'locations'       => [],
        'services'        => [],
        'staff'           => [],
        'serviceCategory' => [],
        'workflows'       => []
    ];

    public function __construct(array $template)
    {
        $this->data       = json_decode($template[ 'data' ], true);
        $this->fromServer = (bool) $template[ 'from_server' ];
    }

    /**
     * @param Collection[] $templates
     * @return void
     */
    public static function applyMultiple(array $templates): void
    {
        //reset initial data of the user
        self::reset();

        //apply default templates one by one
        foreach ($templates as $template) {
            $applier = new static($template->toArray());

            $applier->apply();
        }
    }

    public static function setTenantId(int $id): void
    {
        self::$reservedTenantId = Permission::tenantId() ?: 0;

        Permission::setTenantId($id);
    }

    public static function unsetTenantId(): void
    {
        Permission::setTenantId(self::$reservedTenantId);
    }

    private static function reset(): void
    {
        ServiceStaff::query()->where('staff_id', Staff::query()->select('id'))->delete();
        Staff::query()->delete();

        Service::query()->delete();
        ServiceCategory::query()->delete();

        Location::query()->delete();

        WorkflowAction::query()->where('workflow_id', Workflow::query()->select('id'))->delete();
        Workflow::query()->delete();

        Timesheet::query()->delete();

        Appearance::query()->delete();
    }

    public function apply(): void
    {
        $this->createLocations();

        $this->createServiceCategories();
        $this->createServices();

        $this->createStaff();
        $this->createServiceStaff();

        $this->createWorkflows();
        $this->createWorkflowActions();

        $this->createTimesheets();

        $this->createAppearances();

        $this->createSettings();

        do_action('bkntc_template_apply_template', $this);
    }

    public function isEnabled(string $key): bool
    {
        $columns = $this->get('columns');

        return $columns[$key] ?? false;
    }

    /*----------------------------MODIFIERS----------------------------*/

    private function modifyRow(array $row): array
    {
        $oldId = $row[ 'id' ];

        unset($row[ 'id' ]);

        return [ $row, $oldId ];
    }

    private function modifyStaffLoc(array $staff): array
    {
        $oldLocations = explode(',', $staff[ 'locations' ]);
        $newLocations = [];

        foreach ($oldLocations as $oldLocId) {
            $newLocations[] = $this->newIds[ 'locations' ][ $oldLocId ];
        }

        $staff[ 'locations' ] = implode(',', $newLocations);

        return $staff;
    }

    private function modifyWorkflowData(string $strData): string
    {
        $data = json_decode($strData, true);

        foreach ($data as $k => $datum) {
            //the data we are going to modify stored as an array inside the $data
            if (! is_array($datum)) {
                continue;
            }

            //check if it's one of the data fields we are supposed to change
            if (! isset($this->newIds[ $k ])) {
                continue;
            }

            //ignore the data if it's empty
            if (empty($datum)) {
                continue;
            }

            //update datum with the modified value
            $data[ $k ] = $this->modifyWorkflowDataOldIds($datum);
        }

        return json_encode($data);
    }

    /**
     * updates old ids of the given data to the newly created ones
     */
    private function modifyWorkflowDataOldIds(array $data): array
    {
        foreach ($data as $k => $v) {
            $data[ $k ] = $this->newIds[ 'locations' ][ $v ];
        }

        return $data;
    }

    public function upload(string $image, string $module): string
    {
        if (! $this->fromServer && Helper::isSaaSVersion()) {
            return apply_filters('bkntc_template_upload_image', $image, $module);
        }

        $rand    = md5(base64_encode(rand(1, 9999999) . microtime(true)));
        $newName = $rand . '.' . pathinfo($image, PATHINFO_EXTENSION);
        $newPath = Helper::uploadedFile($newName, $module);

        FSCodeAPIClient::uploadFileFromName(sprintf('%s/%s', $module, $image), $newPath);

        return $newName;
    }
}
