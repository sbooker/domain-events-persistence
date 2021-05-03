<?php

declare(strict_types=1);

namespace Sbooker\DomainEvents\Persistence;

use Sbooker\DomainEvents\DomainEvent;
use Sbooker\DomainEvents\Publisher;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class PersistentPublisher implements Publisher
{
    private WriteStorage $storage;

    private EventNameGiver $nameGiver;

    private NormalizerInterface $normalizer;

    private ?PositionGenerator $positionGenerator;

    public function __construct(WriteStorage $storage, EventNameGiver $nameGiver, NormalizerInterface $normalizer, ?PositionGenerator $positionGenerator = null)
    {
        $this->storage = $storage;
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

        $this->storage->add(PersistentEvent::create($event, $this->nameGiver, $this->normalizer, $position));
    }
}