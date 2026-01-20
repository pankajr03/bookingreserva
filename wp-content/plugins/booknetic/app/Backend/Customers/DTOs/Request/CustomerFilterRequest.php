<?php

namespace BookneticApp\Backend\Customers\DTOs\Request;

class CustomerFilterRequest
{
    private string $search;

    private int $skip;

    private int $limit;

    private string $orderBy;

    private string $orderDirection;

    public function getSearch(): string
    {
        return $this->search;
    }

    public function setSearch(string $search): CustomerFilterRequest
    {
        $this->search = $search;

        return $this;
    }

    public function getSkip(): int
    {
        return $this->skip;
    }

    public function setSkip(int $skip): CustomerFilterRequest
    {
        $this->skip = $skip;

        return $this;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function setLimit(int $limit): CustomerFilterRequest
    {
        $this->limit = $limit;

        return $this;
    }

    public function getOrderBy(): string
    {
        return $this->orderBy;
    }

    public function setOrderBy(string $orderBy): CustomerFilterRequest
    {
        $this->orderBy = $orderBy;

        return $this;
    }

    public function getOrderDirection(): string
    {
        return $this->orderDirection;
    }

    public function setOrderDirection(string $orderDirection): CustomerFilterRequest
    {
        $this->orderDirection = strtolower($orderDirection) === 'asc' ? 'asc' : 'desc';

        return $this;
    }
}
