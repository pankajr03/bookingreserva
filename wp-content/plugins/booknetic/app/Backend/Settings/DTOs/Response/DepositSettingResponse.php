<?php

namespace BookneticApp\Backend\Settings\DTOs\Response;

class DepositSettingResponse
{
    private bool $isEnabled = false;

    private string $type = 'percent';

    private float $value = 0.00;

    public function setIsEnabled(bool $isEnabled): void
    {
        $this->isEnabled = $isEnabled;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function setValue(float $value): void
    {
        $this->value = $value;
    }

    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getValue(): float
    {
        return $this->value;
    }
}
