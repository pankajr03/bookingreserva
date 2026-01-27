<?php

namespace BookneticApp\Backend\Services;

use BookneticApp\Models\Service;
use BookneticApp\Models\ServiceExtra;
use BookneticApp\Models\ServiceStaff;
use BookneticApp\Models\SpecialDay;
use BookneticApp\Models\Staff;
use BookneticApp\Models\Timesheet;
use BookneticApp\Providers\Common\PaymentGatewayService;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Core\CapabilitiesException;
use BookneticApp\Providers\DB\DB;
use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Helpers\Math;
use BookneticApp\Providers\Request\Post;
use Exception;
use RuntimeException;

class ServiceObject
{
    private int $id;
    private string $name;
    private int $category;
    private int $duration;
    private int $hide_duration;
    private int $timeslot_length;
    private $price;
    private int $deposit_enabled;
    private float $deposit;
    private string $deposit_type;
    private int $hide_price;
    private int $buffer_before;
    private int $buffer_after;
    private int $repeatable;
    private int $fixed_full_period;
    private $full_period_type;
    private int $full_period_value;
    private string $repeat_type;
    private string $recurring_payment_type;
    private int $fixed_frequency;
    private int $repeat_frequency;
    private int $max_capacity;
    private array $employees;
    private string $note;
    private string $color;
    private bool $paymentMethodsEnabled = false;
    private array $paymentMethods = [];
    private bool $only_visible_to_staff;
    private int $bring_people;
    private int $minimum_time_required_prior_booking;
    private int $enable_limited_booking_days;
    private int $available_days_for_booking;
    private string $image = '';
    private array $schedule = [];

    private bool $isEdit;
    private array $data = [];

    public function __construct()
    {
        $this->id = Post::int('id');
        $this->name = Post::string('name');
        $this->category = Post::int('category');
        $this->duration = Post::int('duration');
        $this->hide_duration = Post::int('hide_duration', 0, [1]);
        $this->timeslot_length = Post::int('timeslot_length');
        $this->price = Helper::deFormatPrice(Post::string('price'));
        $this->deposit_enabled = Post::int('deposit_enabled', 0, [0, 1]);
        $this->deposit = Post::float('deposit');
        $this->deposit_type = Post::string('deposit_type', '', ['percent', 'price']);
        $this->hide_price = Post::int('hide_price', 0, [1]);
        $this->buffer_before = Post::int('buffer_before');
        $this->buffer_after = Post::int('buffer_after');
        $this->repeatable = Post::int('repeatable', 0, [0, 1]);
        $this->fixed_full_period = Post::int('fixed_full_period', 0, [0, 1]);
        $this->full_period_type = Post::string('full_period_type', '', ['month', 'week', 'day', 'time']);
        $this->full_period_value = Post::int('full_period_value');
        $this->repeat_type = Post::string('repeat_type', '', ['monthly', 'weekly', 'daily']);
        $this->recurring_payment_type = Post::string('recurring_payment_type', 'first_month', ['first_month', 'full']);
        $this->fixed_frequency = Post::int('fixed_frequency', 0, [0, 1]);
        $this->repeat_frequency = Post::int('repeat_frequency');
        $this->max_capacity = Post::int('max_capacity');
        $this->note = Post::string('note');
        $this->color = Post::string('color');
        $this->only_visible_to_staff = Post::int('only_visible_to_staff', 0, [0, 1]) === 1;
        $this->bring_people = Post::int('bring_people', 1, [0, 1]);
        $this->minimum_time_required_prior_booking = Post::int('minimum_time_required_prior_booking');
        $this->enable_limited_booking_days = Post::int('enable_limited_booking_days');
        $this->available_days_for_booking = Post::int('available_days_for_booking');

        $this->isEdit = $this->id > 0;
    }

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @throws CapabilitiesException
     */
    private function hasCapability(): void
    {
        if ($this->isEdit) {
            Capabilities::must('services_edit');
        } else {
            Capabilities::must('services_add');
        }
    }

    /**
     * @throws Exception
     */
    public function validate(): void
    {
        $this->hasCapability();

        if (empty($this->name)) {
            throw new RuntimeException(bkntc__('Please fill in the "name" field correctly!'));
        }

        if (is_null($this->price)) {
            throw new RuntimeException(bkntc__('Price field is required!'));
        }

        if (!($this->duration > 0)) {
            throw new RuntimeException(bkntc__('Duration field must be greater than zero!'));
        }

        if ($this->max_capacity < 0) {
            throw new RuntimeException(bkntc__('Capacity field is wrong!'));
        }

        $this->checkAllowedServiceLimit();
        $this->validateDeposit();
        $this->validateName();
        $this->checkIfRecurringEnabled();
    }

    /**
     * @throws Exception
     * */
    private function validateName(): void
    {
        $check = Service::query()
            ->where('name', $this->name)
            ->where('category_id', $this->category)
            ->where('id', '!=', $this->id)
            ->count();

        if ($check) {
            throw new RuntimeException(bkntc__('This service name is already exist! Please choose an other name.'));
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    private function validateDeposit(): void
    {
        if (!$this->deposit_enabled || (Helper::isSaaSVersion() && !Capabilities::tenantCan('disable_deposit_payments'))) {
            return;
        }

        if ($this->deposit === 0.0) {
            throw new RuntimeException(bkntc__('Deposit field is required!'));
        }

        if (empty($this->deposit_type)) {
            throw new RuntimeException(bkntc__('Deposit type field is required!'));
        }

        if (($this->deposit_type === 'percent' && $this->deposit > 100) || ($this->deposit_type === 'price' && $this->deposit > $this->price)) {
            throw new RuntimeException(bkntc__('Deposit can not exceed the price!'));
        }
    }

    public function isEdit(): bool
    {
        return $this->isEdit;
    }

    /**
     * @throws Exception
     */
    public function populateData(): void
    {
        $this->initData();
        $this->parseWeeklySchedule();
        $this->parseStaffList();
        $this->handleImage();
        $this->handleCustomPaymentMethods();
        $this->applyFilters();
    }

    public function initData(): void
    {
        $this->data = [
            'name' => $this->name,
            'price' => Math::floor($this->price),
            'deposit' => $this->deposit_enabled === 1 ? Math::floor($this->deposit) : Math::floor(0),
            'deposit_type' => $this->deposit_type,
            'hide_price' => $this->hide_price,
            'hide_duration' => $this->hide_duration,

            'category_id' => $this->category,
            'duration' => $this->duration,
            'timeslot_length' => $this->timeslot_length,
            'buffer_before' => $this->buffer_before,
            'buffer_after' => $this->buffer_after,

            'is_recurring' => $this->repeatable,

            'full_period_type' => $this->fixed_full_period ? $this->full_period_type : null,
            'full_period_value' => $this->fixed_full_period ? $this->full_period_value : 0,

            'repeat_type' => $this->repeat_type,
            'recurring_payment_type' => $this->recurring_payment_type,

            'repeat_frequency' => $this->fixed_frequency ? $this->repeat_frequency : 0,

            'max_capacity' => $this->max_capacity,

            'notes' => $this->note,
            'color' => $this->color,

            'is_visible' => 1
        ];
    }

    /**
     * @throws Exception
     */
    private function checkAllowedServiceLimit(): void
    {
        if ($this->isEdit) {
            return;
        }

        $allowedLimit = Capabilities::getLimit('services_allowed_max_number');

        if ($allowedLimit > -1 && Service::query()->count() >= $allowedLimit) {
            throw new RuntimeException(bkntc__('You can\'t add more than %d Service. Please upgrade your plan to add more Service.', [$allowedLimit]));
        }
    }

    /**
     * @throws Exception
     */
    private function checkIfRecurringEnabled(): void
    {
        if (!$this->repeatable) {
            $this->fixed_full_period = 0;
            $this->repeat_type = '';
            $this->fixed_frequency = 0;
            $this->recurring_payment_type = '';

            return;
        }

        if ($this->fixed_full_period && (empty($this->full_period_type) || empty($this->full_period_value))) {
            throw new RuntimeException(bkntc__('Please fill "Full period" field!'));
        }

        if (empty($this->repeat_type)) {
            throw new RuntimeException(bkntc__('Please fill "Repeat" field!'));
        }

        if ($this->fixed_frequency && empty($this->repeat_frequency)) {
            throw new RuntimeException(bkntc__('Please fill "Frequency" field!'));
        }
    }

    /**
     * @throws Exception
     */
    public function parseWeeklySchedule(): void
    {
        $schedule = Post::string('weekly_schedule');

        // check weekly schedule array
        if (empty($schedule)) {
            throw new RuntimeException(bkntc__('Please fill the weekly schedule correctly!'));
        }

        $schedule = json_decode($schedule, true);

        if (empty($schedule) || !is_array($schedule) || count($schedule) !== 7) {
            return;
        }

        foreach ($schedule as $dayInfo) {
            if ($this->validateDayInfo($dayInfo)) {
                throw new RuntimeException(bkntc__('Please fill the weekly schedule correctly!'));
            }

            $isDayOff = $dayInfo['day_off'];
            $timeEnd = $dayInfo['end'] === "24:00" ? "24:00" : Date::timeSQL($dayInfo['end']);
            $breaks = $isDayOff ? [] : $dayInfo['breaks'];

            $newBreaks = [];

            foreach ($breaks as $break) {
                if (is_array($break)
                    && isset($break[0], $break[1])
                    && is_string($break[0])
                    && is_string($break[1])
                    && Date::epoch($break[1]) > Date::epoch($break[0])
                ) {
                    $newBreaks[] = [Date::timeSQL($break[0]), Date::timeSQL($break[1])];
                }
            }

            $this->schedule[] = [
                'day_off' => $isDayOff,
                'start' => $isDayOff ? '' : Date::timeSQL($dayInfo['start']),
                'end' => $isDayOff ? '' : $timeEnd,
                'breaks' => $newBreaks,
            ];
        }
    }

    private function validateDayInfo($info): bool
    {
        return !(
            isset($info['start'], $info['end'], $info['day_off'], $info['breaks']) &&
            is_string($info['start']) &&
            is_string($info['end']) &&
            is_numeric($info['day_off']) &&
            is_array($info['breaks'])
        );
    }

    /**
     * @throws Exception
     */
    public function parseStaffList(): void
    {
        $employees = Post::string('employees');
        $employees = json_decode($employees, true);
        $employees = is_array($employees) ? $employees : [];

        //todo://bunları string key-lərlə əvəzləmək lazımdı, burdan heç kim heç nə başa düşmür.
        foreach ($employees as $staff) {
            if (
                isset($staff[0], $staff[1], $staff[2], $staff[3]) &&
                is_numeric($staff[0]) && $staff[0] > 0 &&
                is_numeric($staff[1]) && $staff[1] >= -1 &&
                is_numeric($staff[2]) && $staff[2] >= -1 &&
                is_string($staff[3]) &&
                in_array($staff[3], ['percent', 'price'])
            ) {
                if (isset($this->employees[(int)$staff[0]])) {
                    throw new RuntimeException(bkntc__('Duplicate Staff selected!'));
                }

                if ($staff[1] != -1 && (($staff[3] === 'percent' && $staff[2] > 100) || ($staff[3] === 'price' && $staff[2] > $staff[1]))) {
                    throw new RuntimeException(bkntc__('Deposit can not exceed the price!'));
                }

                $this->employees[(int)$staff[0]] = [
                    Math::floor($staff[1]),
                    Math::floor($staff[2]),
                    $staff[3]
                ];
            }
        }
    }

    /**
     * @throws Exception
     */
    public function handleImage(): void
    {
        if (!isset($_FILES['image']) || !is_string($_FILES['image']['tmp_name'])) {
            return;
        }

        $pathInfo = pathinfo($_FILES["image"]["name"]);
        $extension = strtolower($pathInfo['extension']);

        if (!in_array($extension, ['jpg', 'jpeg', 'png'])) {
            throw new RuntimeException(bkntc__('Only JPG and PNG images allowed!'));
        }

        $this->image = md5(base64_encode(rand(1, 9999999) . microtime(true))) . '.' . $extension;
        $fileName = Helper::uploadedFile($this->image, 'Services');

        move_uploaded_file($_FILES['image']['tmp_name'], $fileName);

        $this->data['image'] = $this->image;
    }

    /**
     * @throws Exception
     */
    public function handleCustomPaymentMethods(): void
    {
        $this->paymentMethodsEnabled = Post::int('custom_payment_methods_enabled', 0, [1]) === 1;
        $selectedMethods = explode(',', Post::string('custom_payment_methods'));
        $this->paymentMethods = array_intersect($selectedMethods, PaymentGatewayService::getInstalledGatewayNames());

        if ($this->paymentMethodsEnabled && empty($selectedMethods)) {
            throw new RuntimeException(bkntc__('At least one payment method should be selected!'));
        }
    }

    public function applyFilters(): void
    {
        $this->data = apply_filters('service_sql_data', $this->data);
    }

    /**
     * Saves the current object.
     * @throws Exception
     */
    public function save(): void
    {
        if ($this->isEdit) {
            $this->update();
        } else {
            $this->insert();
        }

        $this->saveOptions();
        $this->saveSettings();
        $this->saveWeeklySchedule();
        $this->saveSpecialDays();
        $this->saveServiceStaff();
        $this->saveServiceExtras();
        $this->saveServiceExtraLimits();
        $this->saveOrderOption();
        $this->saveTranslations();
    }

    private function update(): void
    {
        if (empty($this->image)) {
            unset($this->data['image']);
        } else {
            $this->removeOldImage();
        }

        Service::query()->where('id', $this->id)->update($this->data);
        ServiceStaff::query()->where('service_id', $this->id)->delete();
        Timesheet::query()->where('service_id', $this->id)->delete();
    }

    private function insert(): void
    {
        $this->data['is_active'] = 1;

        Service::query()->insert($this->data);

        $this->id = DB::lastInsertedId();
    }

    private function removeOldImage(): void
    {
        $oldInfo = Service::query()->get($this->id);

        if (empty($oldInfo['image'])) {
            return;
        }

        $filePath = Helper::uploadedFile($oldInfo['image'], 'Services');

        if (is_file($filePath) && is_writable($filePath)) {
            unlink($filePath);
        }
    }

    private function saveOptions(): void
    {
        if (Helper::getMinTimeRequiredPriorBooking() == $this->minimum_time_required_prior_booking) {
            $this->minimum_time_required_prior_booking = -1; // it should always be equal to default settings until it is manually changed
        }

        Service::setData($this->id, 'bring_people', $this->bring_people);
        Service::setData($this->id, 'only_visible_to_staff', $this->only_visible_to_staff);
        Service::setData($this->id, 'minimum_time_required_prior_booking', $this->minimum_time_required_prior_booking);
    }

    public function saveSettings(): void
    {
        Service::setData($this->id, 'enable_limited_booking_days', $this->enable_limited_booking_days);
        Service::setData($this->id, 'available_days_for_booking', $this->available_days_for_booking);

        $this->savePaymentSettings();
    }

    private function savePaymentSettings(): void
    {
        if ($this->isEdit && !$this->paymentMethodsEnabled) {
            Service::deleteData($this->id, 'custom_payment_methods');
        } elseif ($this->paymentMethodsEnabled) {
            Service::setData($this->id, 'custom_payment_methods', json_encode($this->paymentMethods));
        }
    }

    private function saveWeeklySchedule(): void
    {
        if (empty($this->schedule)) {
            return;
        }

        Timesheet::query()
            ->insert([
                'timesheet' => json_encode($this->schedule),
                'service_id' => $this->id
            ]);
    }

    private function saveSpecialDays(): void
    {
        $specialDays = Helper::_post('special_days', '', 'string');

        $specialDays = json_decode($specialDays, true);
        $specialDays = is_array($specialDays) ? $specialDays : [];

        $specialDayIds = [];

        foreach ($specialDays as $day) {
            if (
                !(
                    isset($day['date'], $day['start'], $day['end'], $day['breaks']) &&
                    is_string($day['date']) &&
                    is_string($day['start']) &&
                    is_string($day['end']) &&
                    is_array($day['breaks'])
                )
            ) {
                continue;
            }

            $spId = isset($day['id']) ? (int)$day['id'] : 0;
            $date = Date::dateSQL(Date::reformatDateFromCustomFormat($day['date']));

            $newBreaks = [];

            foreach ($day['breaks'] as $break) {
                if (
                    isset($break[0], $break[1]) &&
                    is_array($break) &&
                    is_string($break[0]) &&
                    is_string($break[1]) &&
                    Date::epoch($break[1]) > Date::epoch($break[0])
                ) {
                    $newBreaks[] = [Date::timeSQL($break[0]), $break[1] === "24:00" ? "24:00" : Date::timeSQL($break[1])];
                }
            }

            $timesheet = json_encode([
                'day_off' => 0,
                'start' => Date::time($day['start']),
                'end' => $day['end'] === "24:00" ? "24:00" : Date::timeSQL($day['end']),
                'breaks' => $newBreaks,
            ]);

            if ($spId > 0) {
                SpecialDay::query()->where('id', $spId)
                    ->where('service_id', $this->id)
                    ->update([
                        'timesheet' => $timesheet,
                        'date' => $date
                    ]);

                $specialDayIds[] = $spId;
            } else {
                SpecialDay::query()->insert([
                    'timesheet' => $timesheet,
                    'date' => $date,
                    'service_id' => $this->id
                ]);

                $specialDayIds[] = SpecialDay::lastId();
            }
        }

        if (!$this->isEdit) {
            return;
        }

        $oldDays = SpecialDay::query()->where('service_id', $this->id);

        if (!empty($specialDayIds)) {
            $oldDays = $oldDays->where('id', 'not in', $specialDayIds);
        }

        $oldDays->delete();
    }

    private function saveServiceStaff(): void
    {
        if (!Capabilities::tenantCan('staff')) {
            $this->employees = [
                Staff::query()->limit(1)->fetch()->id => [-1, -1, 'percent']
            ];
        }

        if (empty($this->employees)) {
            return;
        }

        foreach ($this->employees as $staffId => $price) {
            ServiceStaff::query()->insert([
                'service_id' => $this->id,
                'staff_id' => $staffId,
                'price' => $price[0],
                'deposit' => $price[1],
                'deposit_type' => $price[2]
            ]);
        }
    }

    private function saveServiceExtras(): void
    {
        if ($this->isEdit) {
            return;
        }

        $extras = Helper::_post('extras', '', 'string');
        $extras = json_decode($extras, true);

        $extras1 = [];

        foreach ($extras as $extraId) {
            if (is_numeric($extraId) && $extraId > 0) {
                $extras1[] = (int)$extraId;
            }
        }

        if (empty($extras1)) {
            return;
        }

        ServiceExtra::query()->where('id', $extras1)->update(['service_id' => $this->id]);
    }

    /**
     * todo://burda refactoring etmek olar mence
     */
    private function saveOrderOption(): void
    {
        $orderOption = json_decode(Helper::getOption("services_order", '[]'), true);

        if (empty($orderOption) || !is_array($orderOption)) {
            return;
        }

        $savedCategory = $orderOption[$this->category] ?? [];

        if ($this->isEdit) {
            /**
             * Eger edit edirikse onda baxiriqki servisin kateqoriyasi deyisib ya yox
             * Eger kateqoriya deyismeyibse onda hecne etmirik
             * */
            if (!in_array($this->id, $savedCategory)) {
                /** First find service's previous category*/
                $previousCategory = null;

                foreach ($orderOption as $key => $serviceIDS) {
                    if (in_array($this->id, $serviceIDS)) {
                        $previousCategory = $key;
                    }
                }

                /** Eger kateqoriya idsi tapilirsa, servisin kateqoriyasini deyisdiyimiz ucun, serivisin id-ni array dan silirik*/
                if (!is_null($previousCategory) && isset($orderOption[$previousCategory])) {
                    $previousCategoryData = $orderOption[$previousCategory];

                    if (($key = array_search($this->id, $previousCategoryData)) !== false) {
                        unset($previousCategoryData[$key]);
                    }

                    $orderOption[$previousCategory] = $previousCategoryData;
                }

                $savedCategory[] = $this->id;
                $orderOption[$this->category] = $savedCategory;
            }
        } else {
            /** If not editing just insert new id*/
            $savedCategory[] = $this->id;
            $orderOption[$this->category] = $savedCategory;
        }

        Helper::setOption("services_order", json_encode($orderOption));
    }

    private function saveTranslations(): void
    {
        Service::handleTranslation($this->id);
    }

    /**
     * @throws Exception
     */
    private function saveServiceExtraLimits(): void
    {
        $minLimitEnabled = Post::int('service_extra_min_limit_enabled', 0, [1]);
        $maxLimitEnabled = Post::int('service_extra_max_limit_enabled', 0, [1]);

        $min = $minLimitEnabled ? Post::int('service_extra_min_limit') : 0;
        $max = $maxLimitEnabled ? Post::int('service_extra_max_limit') : 0;

        $this->validateServiceExtraLimits($min, $max);

        $encodedLimitations = json_encode([
            'min' => $min,
            'max' => $max,
        ]);

        Service::setData(
            $this->id,
            'service_extra_limitations',
            $encodedLimitations
        );
    }

    /**
     * @throws Exception
     */
    private function validateServiceExtraLimits(int $min, int $max): void
    {
        if ($min < 0) {
            throw new RuntimeException(bkntc__("Service extra limiter minimum value must be a positive number"));
        }

        if ($max < 0) {
            throw new RuntimeException(bkntc__("Service extra limiter maximum value must be a positive number"));
        }

        if ($min > $max && $max !== 0) {
            throw new RuntimeException(bkntc__("Service extra limiter minimum limit can't exceed the maximum limit."));
        }

        $extras = ServiceExtra::query()
            ->select([
                'is_active',
                'min_quantity',
            ])
            ->where('service_id', $this->id)
            ->fetchAll();

        if (empty($extras)) {
            return;
        }

        $serviceExtraCount = count($extras);

        if (($min !== 0 && $min > $serviceExtraCount) || ($max !== 0 && $max > $serviceExtraCount)) {
            throw new RuntimeException(bkntc__("Invalid service extra limiter value"));
        }

        $totalMinRequired = 0;

        // edge case -> each service extra might have its own limitations
        foreach ($extras as $extra) {
            if (empty($extra['is_active']) || $extra['is_active'] !== '1') {
                continue;
            }

            if (empty($extra['min_quantity']) || $extra['min_quantity'] <= 0) {
                continue;
            }

            $totalMinRequired++;
        }

        if (($min !== 0 && $min < $totalMinRequired) || ($max !== 0 && $max < $totalMinRequired)) {
            throw new RuntimeException(bkntc__("Your service extra count and the quantity of service extras are in conflict!"));
        }
    }
}
