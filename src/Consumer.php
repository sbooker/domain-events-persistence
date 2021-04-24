<?php

declare(strict_types=1);

namespace Sbooker\DomainEvents\Persistence;

use Psr\Log\LoggerInterface;
use Sbooker\DomainEvents\DomainEvent;
use Sbooker\DomainEvents\DomainEventSubscriber;
use Sbooker\PersistentPointer;
use Sbooker\TransactionManager\TransactionManager;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class Consumer
{
    private EventStorage $eventStorage;

    private TransactionManager $transactionManager;

    private DenormalizerInterface $denormalizer;

    private PersistentPointer\Repository $positionStorage;

    private EventNameGiver $nameGiver;

    private DomainEventSubscriber $subscriber;

    private string $name;

    private ?LoggerInterface $logger;

    public function __construct(
        EventStorage $eventStorage,
        TransactionManager $transactionManager,
        DenormalizerInterface $denormalizer,
        PersistentPointer\Repository $positionStorage,
        EventNameGiver $nameGiver,
        DomainEventSubscriber $subscriber,
        string $name,
        ?LoggerInterface $logger = null
    ) {
        $this->eventStorage = $eventStorage;
        $this->transactionManager = $transactionManager;
        $this->denormalizer = $denormalizer;
        $this->positionStorage = $positionStorage;
        $this->nameGiver = $nameGiver;
        $this->subscriber = $subscriber;
        $this->name = $name;
        $this->logger = $logger;
    }

    /**
     * @throws \Throwable
     */
    public function consume(): bool
    {
        return
            $this->transactionManager->transactional(function (): bool {
                $position = $this->positionStorage->getLocked($this->getName());;

                $this->debug('Consume event after position #{p}', ['p' => $position->getValue()]);

                $event = $this->eventStorage->getFirstByPosition($this->getConsumableEventNames(),  $position->getValue());

                if (!$event) {
                    $this->debug('No events found');
                    return false;
                }

                $this->debug('Consume event at position #{p}', ['p' => $event->getPosition()]);

                /** @var DomainEvent $domainEvent */
                $domainEvent = $this->denormalizer->denormalize(
                    $event->getPayload(),
                    $this->nameGiver->getClass($event->getName()),
                );

                $this->handleEvent($domainEvent);

                if (null === $event->getPosition()) {
                    throw new \RuntimeException('Event must have position to consume');
                }

                $position->increaseTo($event->getPosition());

                return true;
            });
    }

    public function getConsumableEventNames(): array
    {
        return
            array_map(
                [ $this->nameGiver, 'getNameByClass' ],
                $this->getSubscriber()->getListenedEventClasses()
            );
    }

    private function getName(): string
    {
        return $this->name;
    }

    /**
     * @param DomainEvent $event
     * @throws \Exception
     */
    private function handleEvent(DomainEvent $event): void
    {
        $this->getSubscriber()->handleEvent($event);
    }

    public function getSubscriber(): DomainEventSubscriber
    {
        return $this->subscriber;
    }

    private function debug(string $message, array $context = []): void
    {
        if (!$this->logger) {
            return;
        }

        $this->logger->debug($message, $context);
    }
}