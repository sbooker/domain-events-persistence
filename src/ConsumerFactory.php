<?php

declare(strict_types=1);

namespace Sbooker\DomainEvents\Persistence;

use Psr\Log\LoggerInterface;
use Sbooker\DomainEvents\DomainEventSubscriber;
use Sbooker\TransactionManager\TransactionManager;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class ConsumerFactory
{
    private ConsumeStorage $eventStorage;
    private TransactionManager $transactionManager;
    private EventNameGiver $nameGiver;
    private DenormalizerInterface $denormalizer;
    private ?LoggerInterface $logger;
    public function __construct(
        ConsumeStorage $eventStorage,
        TransactionManager $transactionManager,
        EventNameGiver $nameGiver,
        DenormalizerInterface $denormalizer,
        ?LoggerInterface $logger = null
    ) {
        $this->eventStorage = $eventStorage;
        $this->transactionManager = $transactionManager;
        $this->nameGiver = $nameGiver;
        $this->denormalizer = $denormalizer;
        $this->logger = $logger;
    }

    public function createBySubscriber(string $name, DomainEventSubscriber $subscriber): Consumer
    {
        return
            $this->createByHandler(
                $name,
                new ConsumerSubscriberBridge(
                    $this->nameGiver,
                    $this->denormalizer,
                    $subscriber
                )
            );
    }

    public function createByHandler(string $name, PersistentEventHandler $handler): Consumer
    {
        return
            new Consumer(
                $this->eventStorage,
                $this->transactionManager,
                $handler,
                $name,
                $this->logger
            );
    }
}