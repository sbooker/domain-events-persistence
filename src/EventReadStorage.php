<?php

declare(strict_types=1);

namespace Sbooker\DomainEvents\Persistence;

use Ramsey\Uuid\UuidInterface;

interface EventReadStorage
{
    /**
     * @return array<PersistentEvent>
     */
    public function getByEntityIdAndPositions(UuidInterface $entityId, ?int $afterPosition = null, ?int $beforePosition = null, bool $orderByPositionAsc = true): array;

    /**
     * @return array<PersistentEvent>
     */
    public function getByEntityIdAnOccurredAt(UuidInterface $entityId, ?\DateTimeImmutable $after = null, ?\DateTimeImmutable $before = null, bool $orderByPositionAsc = true): array;
}