<?php

declare(strict_types=1);

namespace Test\Sbooker\DomainEvents\Persistence;

use Ramsey\Uuid\Uuid;
use Sbooker\DomainEvents\DomainEvent;
use Sbooker\DomainEvents\DomainEventSubscriber;
use Sbooker\DomainEvents\Persistence\ClassNameNameGiver;
use Sbooker\DomainEvents\Persistence\ConsumerSubscriberBridge;
use Sbooker\DomainEvents\Persistence\PersistentEvent;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class ConsumerSubscriberBridgeTest extends TestCase
{
    public function test(): void
    {
        $entityId = Uuid::uuid4();
        $domainEvent = new TestDomainEvent($entityId);
        $payload = ['entity_id' => $entityId->toString()];
        $persistentEvent = new PersistentEvent(Uuid::uuid4(), TestDomainEvent::class, $domainEvent->getOccurredAt(), $domainEvent->getEntityId(), $payload, 1);
        $bridge =
            new ConsumerSubscriberBridge(
                new ClassNameNameGiver(),
                $this->getDenormalizer($payload, TestDomainEvent::class, $domainEvent),
                $this->getSubscriber($domainEvent)
            );

        $eventNames = $bridge->getHandledEventNames();
        $bridge->handle($persistentEvent);

        $this->assertEquals([TestDomainEvent::class], $eventNames);
    }

    private function getSubscriber(DomainEvent $domainEvent): DomainEventSubscriber
    {
        $mock = $this->createMock(DomainEventSubscriber::class);
        $mock->expects($this->once())->method('handleEvent')->with($domainEvent);
        $mock->expects($this->once())->method('getListenedEventClasses')->willReturn([TestDomainEvent::class]);

        return $mock;
    }

    private function getDenormalizer(array $payload, string $class, DomainEvent $domainEvent): DenormalizerInterface
    {
        $mock = $this->createMock(DenormalizerInterface::class);
        $mock->expects($this->once())->method('denormalize')->with($payload, $class)->willReturn($domainEvent);

        return $mock;
    }
}