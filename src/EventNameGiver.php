<?php

declare(strict_types=1);

namespace Sbooker\DomainEvents\Persistence;

use Sbooker\DomainEvents\DomainEvent;

abstract class EventNameGiver
{
    final public function getName(DomainEvent $event): string
    {
        return $this->getNameByClass(get_class($event));
    }

    abstract public function getNameByClass(string $class): string;

    abstract public function getClass(string $name): string;
}