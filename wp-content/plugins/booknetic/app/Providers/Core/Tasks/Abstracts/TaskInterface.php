<?php

namespace BookneticApp\Providers\Core\Tasks\Abstracts;

interface TaskInterface
{
    public function execute(): void;
    public function canExecute(): bool;
    public function getTaskName(): string;
}
