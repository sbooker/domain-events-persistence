<?php

declare(strict_types=1);

namespace Sbooker\DomainEvents\Persistence;

interface ConsumeStorage
{
    /**
     * @param string[] $eventNames
     * @param int $position
     *
     * @return PersistentEvent|null
     */
    public function getFirstByPosition(array $eventNames, int $position): ?PersistentEvent;
}