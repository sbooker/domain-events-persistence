<?php

declare(strict_types=1);

namespace Test\Sbooker\DomainEvents\Persistence;

use Sbooker\DomainEvents\DomainEvent;
use Sbooker\DomainEvents\DomainEventSubscriber;
use Sbooker\DomainEvents\Persistence\ClassNameNameGiver;
use Sbooker\DomainEvents\Persistence\Consumer;
use Sbooker\DomainEvents\Persistence\ConsumeStorage;
use Sbooker\DomainEvents\Persistence\WriteStorage;
use Sbooker\DomainEvents\Persistence\PersistentEvent;
use Symfony\Component\Serializer\Serializer;

final class NoEventConsumerTest extends TestCase
{
    public function test(): void
    {
        $consumer = new Consumer(
            $this->getEmptyEventStorage(),
            $this->getTransactionManager(),
            new Serializer(),
            $this->getEmptyPositionStorage(),
            new ClassNameNameGiver(),
            $this->getDummySubscriber(),
            "consumer"
        );

        $result = $consumer->consume();

        $this->assertFalse($result);
    }

    private function getEmptyEventStorage(): ConsumeStorage
    {
        return new class implements ConsumeStorage {
            public function getFirstByPosition(array $eventNames, int $position): ?PersistentEvent
            {
                return null;
            }
        };
    }

    private function getDummySubscriber(): DomainEventSubscriber
    {
        return new class implements DomainEventSubscriber {

            public function getListenedEventClasses(): array
            {
                return [];
            }

            public function handleEvent(DomainEvent $event): void { }
        };
    }
}