<?php

namespace BookneticApp\Backend\Payments\DTOs;

use BookneticApp\Backend\Payments\Exceptions\InvalidPaymentDataException;

class SavePaymentRequest
{
    private int $appointmentId;
    private array $prices;
    private ?float $paidAmount = null;
    private string $status;

    public function getAppointmentId(): int
    {
        return $this->appointmentId;
    }

    public function getPrices(): array
    {
        return $this->prices;
    }

    public function getPaidAmount(): ?float
    {
        return $this->paidAmount;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @throws InvalidPaymentDataException
     */
    public function setAppointmentId(int $appointmentId): SavePaymentRequest
    {
        if ($appointmentId <= 0) {
            throw new InvalidPaymentDataException(bkntc__('Invalid input data provided for saving payment.'));
        }

        $this->appointmentId = $appointmentId;

        return $this;
    }

    public function setPrices(array $prices): SavePaymentRequest
    {
        $this->prices = $prices;

        return $this;
    }

    /**
     * @throws InvalidPaymentDataException
     */
    public function setPaidAmount(float $paidAmount): SavePaymentRequest
    {
        if ($paidAmount < 0) {
            throw new InvalidPaymentDataException(bkntc__('Paid amount cannot be negative.'));
        }

        $this->paidAmount = $paidAmount;

        return $this;
    }

    /**
     * @throws InvalidPaymentDataException
     */
    public function setStatus(string $status): SavePaymentRequest
    {
        if (empty($status)) {
            throw new InvalidPaymentDataException(bkntc__('Payment status cannot be empty.'));
        }

        $this->status = $status;

        return $this;
    }
}
