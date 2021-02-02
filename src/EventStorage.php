<?php

declare(strict_types=1);

namespace Sbooker\DomainEvents\Persistence;

interface EventStorage
{
    public function add(PersistentEvent $event): void;

    /**
     * @param string[] $eventNames
     * @param int $position
     *
     * @return PersistentEvent|null
     */
    public function getFirstByPosition(array $eventNames, int $position): ?PersistentEvent;
}