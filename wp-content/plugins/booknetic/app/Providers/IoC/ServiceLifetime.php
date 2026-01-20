<?php

namespace BookneticApp\Providers\IoC;

class ServiceLifetime
{
    public const SINGLETON = 'singleton';
    public const SCOPED = 'scoped';
    public const TRANSIENT = 'transient';
}
