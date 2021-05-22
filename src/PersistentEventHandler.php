<?php

declare(strict_types=1);

namespace Sbooker\DomainEvents\Persistence;

interface PersistentEventHandler
{
    /**
     * @throws \Exception
     */
    public function handle(PersistentEvent $event): void;

    /**
     * @return array<string>
     */
    public function getHandledEventNames(): array;
}