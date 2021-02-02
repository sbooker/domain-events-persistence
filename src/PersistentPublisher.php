<?php

declare(strict_types=1);

namespace Sbooker\DomainEvents\Persistence;

use Sbooker\DomainEvents\DomainEvent;
use Sbooker\DomainEvents\Publisher;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class PersistentPublisher implements Publisher
{
    private EventStorage $storage;

    private EventNameGiver $nameGiver;

    private NormalizerInterface $normalizer;

    public function __construct(EventStorage $storage, EventNameGiver $nameGiver, NormalizerInterface $normalizer)
    {
        $this->storage = $storage;
        $this->nameGiver = $nameGiver;
        $this->normalizer = $normalizer;
    }

    /**
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function publish(DomainEvent $event): void
    {
        $this->storage->add(PersistentEvent::create($event, $this->nameGiver, $this->normalizer));
    }
}