<?php

namespace BookneticApp\Providers\Core;

class PriceComponent
{
    private int $id;

    private string $identifier; // 'appointment', 'extra', 'product', 'tax', 'discount', etc.

    private string $name = '';
    private float $price = 0.00;
    private array $services = [];
    private array $locations = [];

    private array $meta = [];

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): PriceComponent
    {
        $this->id = $id;

        return $this;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): PriceComponent
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): PriceComponent
    {
        $this->name = $name;

        return $this;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function setPrice(float $price): PriceComponent
    {
        $this->price = $price;

        return $this;
    }

    public function getServices(): array
    {
        return $this->services;
    }

    public function setServices(array $services): PriceComponent
    {
        $this->services = $services;

        return $this;
    }

    public function getLocations(): array
    {
        return $this->locations;
    }

    public function setLocations(array $locations): PriceComponent
    {
        $this->locations = $locations;

        return $this;
    }

    public function getMeta(): array
    {
        return $this->meta;
    }

    public function setMeta(array $meta): PriceComponent
    {
        $this->meta = $meta;

        return $this;
    }

    public function increasePrice(float $amount): void
    {
        $this->price += $amount;
    }
}
