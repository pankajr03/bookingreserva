<?php

namespace BookneticApp\Backend\Mobile\Clients\Models;

class MobileAppSeatsResponse
{
    private array $assignedSeats;

    private int $availableSeatCount;

    public function __construct(array $assignedSeats, int $availableSeats)
    {
        $this->assignedSeats = $assignedSeats;
        $this->availableSeatCount = $availableSeats;
    }

    public function getAssignedSeats(): array
    {
        return $this->assignedSeats;
    }

    public function getAvailableSeatCount(): int
    {
        return $this->availableSeatCount;
    }
}
