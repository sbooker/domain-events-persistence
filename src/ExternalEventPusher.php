<?php

declare(strict_types=1);

namespace Sbooker\DomainEvents\Persistence;

use Ramsey\Uuid\UuidInterface;
use Sbooker\DomainEvents\ActorStorage;
use Sbooker\TransactionManager\TransactionManager;
use Sbooker\TransactionManager\UniqueConstraintViolation;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class ExternalEventPusher
{
    private TransactionManager $transactionManager;
    private NormalizerInterface $normalizer;
    private ?ActorStorage $actorStorage;
    private ?PositionGenerator $positionGenerator;

    public function __construct(
        TransactionManager $transactionManager, NormalizerInterface $normalizer, ?ActorStorage $actorStorage = null, ?PositionGenerator $positionGenerator = null)
    {
        $this->transactionManager = $transactionManager;
        $this->positionGenerator = $positionGenerator;
        $this->normalizer = $normalizer;
        $this->actorStorage = $actorStorage;
    }

    /**
     * @throws \Throwable
     */
    public function push(UuidInterface $id, string $name, \DateTimeImmutable $occurredAt, UuidInterface $entityId, array $payload): void
    {
        try {
            $this->transactionManager->transactional(function () use ($id, $name, $occurredAt, $entityId, $payload): void {
                $this->transactionManager->persist(
                    PersistentEvent::fromRaw(
                        $id,
                        $name,
                        $occurredAt,
                        $entityId,
                        $payload,
                        $this->normalizer,
                        $this->actorStorage ? $this->actorStorage->getCurrentActor() : null,
                        $this->positionGenerator ? $this->positionGenerator->next() : null,
                    )
                );
            });
        } catch (UniqueConstraintViolation $exception) {
            return;
        }
    }
}