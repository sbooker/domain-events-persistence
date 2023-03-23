<?php

declare(strict_types=1);

namespace Sbooker\DomainEvents\Persistence;

use Sbooker\DomainEvents\DomainEvent;
use Sbooker\DomainEvents\Publisher;
use Sbooker\TransactionManager\EntityManager;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class PersistentPublisher implements Publisher
{
    private EventNameGiver $nameGiver;
    private NormalizerInterface $normalizer;
    private ?PositionGenerator $positionGenerator;

    private ?EntityManager $entityManager = null;

    public function __construct(EventNameGiver $nameGiver, NormalizerInterface $normalizer, ?PositionGenerator $positionGenerator = null)
    {
        $this->nameGiver = $nameGiver;
        $this->normalizer = $normalizer;
        $this->positionGenerator = $positionGenerator;
    }

    public function withEntityManager(EntityManager $entityManager): void
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function publish(DomainEvent $event): void
    {
        if (null === $this->entityManager) {
            throw new \RuntimeException('Entity manager not sets.');
        }

        $position = null !== $this->positionGenerator ? $this->positionGenerator->next() : null;

        $this->entityManager->persist(PersistentEvent::create($event, $this->nameGiver, $this->normalizer, $position));
    }
}