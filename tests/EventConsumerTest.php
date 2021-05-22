<?php

declare(strict_types=1);

namespace Test\Sbooker\DomainEvents\Persistence;

use Ramsey\Uuid\Uuid;
use Sbooker\DomainEvents\Actor;
use Sbooker\DomainEvents\Persistence\Consumer;
use Sbooker\DomainEvents\Persistence\ConsumeStorage;
use Sbooker\DomainEvents\Persistence\EventStorage;
use Sbooker\DomainEvents\Persistence\PersistentEvent;
use Sbooker\DomainEvents\Persistence\PersistentEventHandler;

class EventConsumerTest extends TestCase
{
    /**
     * @dataProvider actorExamples
     */
    public function test(?Actor $actor, array $actorPayloadPart): void
    {
        $entityId = Uuid::uuid4();
        $occurredAt = new \DateTimeImmutable();
        $payload =
            array_merge(
                [
                    "entityId" => $entityId->toString(),
                    "occurredAt" => $occurredAt->format(self::DATE_FORMAT),
                ],
                $actorPayloadPart
            );
        $persistentEvent = new PersistentEvent(Uuid::uuid4(), TestDomainEvent::class, $occurredAt, Uuid::uuid4(), $payload, 1);
        $consumer = new Consumer(
            $this->getEventStorage([$persistentEvent->getName()], 0, $persistentEvent),
            $this->getTransactionManager(),
            $this->getEmptyPositionStorage(),
            $this->createEventHandler($persistentEvent),
            "consumer"
        );

        $result = $consumer->consume();

        $this->assertTrue($result);
    }

    private function createEventHandler(PersistentEvent $event): PersistentEventHandler
    {
        $mock = $this->createMock(PersistentEventHandler::class);
        $mock->expects($this->once())->method('handle')->with($event);
        $mock->expects($this->once())->method('getHandledEventNames')->willReturn([$event->getName()]);

        return $mock;
    }

    private function getEventStorage(array $eventNames, int $position, PersistentEvent $event): ConsumeStorage
    {
        $mock = $this->createMock(ConsumeStorage::class);
        $mock->expects($this->once())->method('getFirstByPosition')
            ->with($eventNames, $position)
            ->willReturn($event);

        return $mock;
    }
}