<?php

declare(strict_types=1);

namespace Sbooker\DomainEvents\Persistence;

use Sbooker\DomainEvents\DomainEvent;
use Sbooker\DomainEvents\DomainEventSubscriber;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class ConsumerSubscriberBridge implements PersistentEventHandler
{
    private EventNameGiver $nameGiver;

    private DenormalizerInterface $denormalizer;

    private DomainEventSubscriber $subscriber;

    public function __construct(EventNameGiver $nameGiver, DenormalizerInterface $denormalizer, DomainEventSubscriber $subscriber)
    {
        $this->nameGiver = $nameGiver;
        $this->denormalizer = $denormalizer;
        $this->subscriber = $subscriber;
    }

    public function handle(PersistentEvent $event): void
    {
        /** @var DomainEvent $domainEvent */
        $domainEvent =
            $this->denormalizer->denormalize(
                $event->getPayload(),
                $this->nameGiver->getClass($event->getName()),
            );

        $this->subscriber->handleEvent($domainEvent);
    }

    public function getHandledEventNames(): array
    {
        return
            array_map(
                [ $this->nameGiver, 'getNameByClass' ],
                $this->subscriber->getListenedEventClasses()
            );
    }
}