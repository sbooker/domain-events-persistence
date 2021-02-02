<?php

declare(strict_types=1);

namespace Sbooker\DomainEvents\Persistence;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Sbooker\DomainEvents\DomainEvent;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class PersistentEvent
{
    private UuidInterface $id;

    /* autoincrement on persistence layer */
    private ?int $position = null;

    private string $name;

    private \DateTimeImmutable $occurredAt;

    private array $payload;

    function __construct(UuidInterface $id, string $name, \DateTimeImmutable $occurredAt, array $payload, ?int $position = null)
    {
        assert(strlen($name) > 0);
        $this->id = $id;
        $this->name = $name;
        $this->occurredAt = $occurredAt;
        $this->payload = $payload;
        $this->position = $position;
    }

    /**
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public static function create(DomainEvent $event, EventNameGiver $nameGiver, NormalizerInterface $normalizer): self
    {
        return new self(
            Uuid::uuid4(),
            $nameGiver->getName($event),
            $event->getOccurredAt(),
            $normalizer->normalize($event)
        );
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getOccurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }
}