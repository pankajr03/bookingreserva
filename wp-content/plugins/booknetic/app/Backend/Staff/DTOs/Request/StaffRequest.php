<?php

namespace BookneticApp\Backend\Staff\DTOs\Request;

class StaffRequest
{
    public ?int $id = null;
    public ?int $wpUser = null;

    public string $name = '';
    public string $email = '';
    public bool $allowToLogin = false;
    public string $useExistingWpUser = 'yes';
    public string $wpUserPassword = '';
    public bool $updateWpUser = false;

    public string $profession = '';
    public string $phone = '';
    public string $note = '';

    /** @var int[] */
    public array $locations = [];

    /** @var int[] */
    public array $services = [];

    /** @var array[] */
    public array $weeklySchedule = [];

    /** @var array[] */
    public array $specialDays = [];

    /** @var array[] */
    public array $holidays = [];

    /** @var array<string, string>|string[] */
    public array $translations = [];

    /** @var array|null */
    public ?array $image = null;

    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }

        $this->id = (int)($this->id ?? 0);
    }

    public function isEdit(): bool
    {
        return $this->id > 0;
    }
}
