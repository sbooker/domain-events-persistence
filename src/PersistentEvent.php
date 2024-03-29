<?php

declare(strict_types=1);

namespace Sbooker\DomainEvents\Persistence;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Sbooker\DomainEvents\Actor;
use Sbooker\DomainEvents\DomainEvent;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @final
 */
class PersistentEvent
{
    private UuidInterface $id;

    private string $name;

    private \DateTimeImmutable $occurredAt;

    private UuidInterface $entityId;

    /**
     * @var array
     */
    private array $payload;

    /* autoincrement on persistence layer */
    private ?int $position = null;

    public function __construct(UuidInterface $id, string $name, \DateTimeImmutable $occurredAt, UuidInterface $entityId, array $payload, ?int $position = null)
    {
        assert(strlen($name) > 0);
        $this->id = $id;
        $this->name = $name;
        $this->occurredAt = $occurredAt;
        $this->entityId = $entityId;
        $this->payload = $payload;
        $this->position = $position;
    }

    /**
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public static function fromRaw(
        UuidInterface $id,
        string $name,
        \DateTimeImmutable $occurredAt,
        UuidInterface $entityId,
        array $payload,
        NormalizerInterface $normalizer,
        ?Actor $actor = null,
        ?int $position = null
    ): self
    {
        return
            new self(
                $id,
                $name,
                $occurredAt,
                $entityId,
                array_merge(
                    $payload,
                    $normalizer->normalize(new RequiresPayloadProperties($entityId, $occurredAt, $actor), 'json')
                ),
                $position
            );
    }

    /**
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public static function create(DomainEvent $event, EventNameGiver $nameGiver, NormalizerInterface $normalizer, ?int $position = null): self
    {
        $normalized = $normalizer->normalize($event);

        if (!is_array($normalized)) {
            throw new NotNormalizableValueException();
        }

        return new self(
            Uuid::uuid4(),
            $nameGiver->getName($event),
            $event->getOccurredAt(),
            $event->getEntityId(),
            $normalized,
            $position
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

    public function getEntityId(): UuidInterface
    {
        return $this->entityId;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }
}