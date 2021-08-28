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
use Sbooker\DomainEvents\Persistence\PersistentEventHandler;
use Symfony\Component\Serializer\Serializer;

final class NoEventConsumerTest extends TestCase
{
    public function test(): void
    {
        $consumer = new Consumer(
            $this->getEmptyEventStorage(),
            $this->getTransactionManager(),
            $this->getHandler(),
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

    public function getHandler(): PersistentEventHandler
    {
        $mock = $this->createMock(PersistentEventHandler::class);
        $mock->expects($this->never())->method('handle');

        return $mock;
    }
}