<?php

declare(strict_types=1);

namespace Sbooker\DomainEvents\Persistence;

use Sbooker\DomainEvents\DomainEvent;
use Sbooker\DomainEvents\Publisher;
use Sbooker\TransactionManager\TransactionManager;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class PersistentPublisher implements Publisher
{
    private TransactionManager $transactionManager;
    private EventNameGiver $nameGiver;
    private NormalizerInterface $normalizer;
    private ?PositionGenerator $positionGenerator;

    public function __construct(TransactionManager $transactionManager, EventNameGiver $nameGiver, NormalizerInterface $normalizer, ?PositionGenerator $positionGenerator = null)
    {
        $this->transactionManager = $transactionManager;
        $this->nameGiver = $nameGiver;
        $this->normalizer = $normalizer;
        $this->positionGenerator = $positionGenerator;
    }

    /**
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function publish(DomainEvent $event): void
    {
        $position = null !== $this->positionGenerator ? $this->positionGenerator->next() : null;

        $this->transactionManager->persist(PersistentEvent::create($event, $this->nameGiver, $this->normalizer, $position));
    }
}