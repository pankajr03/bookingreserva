<?php

namespace BookneticApp\Backend\Services\DTOs\Response;

class ParentCategoryResponse
{
    private int $id;
    private string $name;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): ParentCategoryResponse
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): ParentCategoryResponse
    {
        $this->name = $name;

        return $this;
    }
}
