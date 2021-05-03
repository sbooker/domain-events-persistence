<?php

declare(strict_types=1);

namespace Sbooker\DomainEvents\Persistence;

interface WriteStorage
{
    public function add(PersistentEvent $event): void;
}