<?php

namespace BookneticApp\Backend\Appearance\DTOs\Response;

class ListAppearanceResponse
{
    /**
     * @var AppearanceResponse[]
     */
    private array $appearances;

    public function __construct(array $appearances)
    {
        $this->appearances = $appearances;
    }

    /**
     * @return AppearanceResponse[]
     */
    public function getAppearances(): array
    {
        return $this->appearances;
    }

    public function getCount(): int
    {
        return count($this->appearances);
    }
}
