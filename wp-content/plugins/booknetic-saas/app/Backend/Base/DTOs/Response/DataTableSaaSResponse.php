<?php

namespace BookneticSaaS\Backend\Base\DTOs\Response;

use BookneticSaaS\Providers\UI\DataTableUI;

class DataTableSaaSResponse
{
    private DataTableUI $table;

    public function setTable(DataTableUI $table)
    {
        $this->table = $table;
    }

    public function getTable(): DataTableUI
    {
        return $this->table;
    }
}
