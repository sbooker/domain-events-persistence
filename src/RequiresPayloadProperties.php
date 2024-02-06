<?php

namespace Sbooker\DomainEvents\Persistence;

use Ramsey\Uuid\UuidInterface;
use Sbooker\DomainEvents\Actor;

/**
 * @internal
 */
final class RequiresPayloadProperties
{
    private UuidInterface $entityId;
    private \DateTimeImmutable $occurredAt;
    private ?Actor $actor;

    public function __construct(UuidInterface $entityId, \DateTimeImmutable $occurredAt, ?Actor $actor = null)
    {
        $this->entityId = $entityId;
        $this->occurredAt = $occurredAt;
        $this->actor = $actor;
    }

    public function getEntityId(): UuidInterface
    {
        return $this->entityId;
    }

    public function getOccurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function getActor(): ?Actor
    {
        return $this->actor;
    }
}