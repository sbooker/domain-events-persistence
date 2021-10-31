<?php

declare(strict_types=1);

namespace Sbooker\DomainEvents\Persistence;

use Sbooker\DomainEvents\DomainEvent;
use Sbooker\DomainEvents\Publisher;
use Sbooker\TransactionManager\TransactionManager;
use Sbooker\TransactionManager\TransactionManagerAware;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class PersistentPublisher implements Publisher, TransactionManagerAware
{
    private ?TransactionManager $transactionManager = null;
    private EventNameGiver $nameGiver;
    private NormalizerInterface $normalizer;
    private ?PositionGenerator $positionGenerator;

    public function __construct(EventNameGiver $nameGiver, NormalizerInterface $normalizer, ?PositionGenerator $positionGenerator = null)
    {
        $this->nameGiver = $nameGiver;
        $this->normalizer = $normalizer;
        $this->positionGenerator = $positionGenerator;
    }

    public function setTransactionManager(TransactionManager $transactionManager): void
    {
        $this->transactionManager = $transactionManager;
        if ($this->positionGenerator instanceof TransactionManagerAware) {
            $this->positionGenerator->setTransactionManager($transactionManager);
        }
    }

    /**
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function publish(DomainEvent $event): void
    {
        if (null === $this->transactionManager) {
            throw new \RuntimeException('Transaction manager not sets.');
        }

        $position = null !== $this->positionGenerator ? $this->positionGenerator->next() : null;

        $this->transactionManager->persist(PersistentEvent::create($event, $this->nameGiver, $this->normalizer, $position));
    }
}