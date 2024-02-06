<?php

declare(strict_types=1);

namespace Test\Sbooker\DomainEvents\Persistence;

use Gamez\Symfony\Component\Serializer\Normalizer\UuidNormalizer;
use Ramsey\Uuid\Uuid;
use Sbooker\DomainEvents\Actor;
use Sbooker\DomainEvents\Persistence\EventNameGiver;
use Sbooker\DomainEvents\Persistence\PersistentEvent;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\Serializer;

final class PersistentEventTest extends TestCase
{
    public function testConstructor(): void
    {
        $id = Uuid::uuid4();
        $name = "event.name";
        $at = new \DateTimeImmutable();
        $entityId = Uuid::uuid4();
        $payload = [ "a" => "A", "b" => "Ba" ];

        $event = new PersistentEvent($id, $name, $at, $entityId, $payload);

        $this->assertEquals($id, $event->getId());
        $this->assertEquals($name, $event->getName());
        $this->assertEquals($at, $event->getOccurredAt());
        $this->assertEquals($entityId, $event->getEntityId());
        $this->assertEquals($payload, $event->getPayload());
        $this->assertNull($event->getPosition());
    }

    /**
     * @dataProvider actorExamples
     */
    public function testCreate(?Actor $actor, array $actorPayloadPart): void
    {
        $entityId = Uuid::uuid4();
        $domainEvent = new TestDomainEvent($entityId, $actor);
        $eventName = "event.name";
        $normalizedEvent = array_merge(
            [
                "entityId" => $entityId->toString(),
                "occurredAt" => $domainEvent->getOccurredAt()->format(self::DATE_FORMAT)
            ],
            $actorPayloadPart
        );

        $event = PersistentEvent::create($domainEvent, $this->getNameGiver($eventName), $this->getNormalizer());

        $this->assertEquals($eventName, $event->getName());
        $this->assertEquals($domainEvent->getOccurredAt(), $event->getOccurredAt());
        $this->assertEquals($normalizedEvent, $event->getPayload());
        $this->assertNull($event->getPosition());
    }

    public function testFromRaw(): void
    {
        $id = Uuid::uuid4();
        $name = "event.name";
        $at = new \DateTimeImmutable();
        $entityId = Uuid::uuid4();
        $payload = [ "a" => "A", "b" => "Ba" ];
        $expectedPayload = [
            "a" => "A",
            "b" => "Ba",
            "entityId" => $entityId->toString(),
            "occurredAt" => $at->format(self::DATE_FORMAT),
            "actor" => null
        ];

        $event = PersistentEvent::fromRaw($id, $name, $at, $entityId, $payload, $this->getNormalizer());

        $this->assertEquals($id, $event->getId());
        $this->assertEquals($name, $event->getName());
        $this->assertEquals($at, $event->getOccurredAt());
        $this->assertEquals($entityId, $event->getEntityId());
        $this->assertEquals($expectedPayload, $event->getPayload());
        $this->assertNull($event->getPosition());
    }

    private function getNameGiver(string $eventName): EventNameGiver
    {
        return new class ($eventName) extends EventNameGiver {
            private string $eventName;

            public function __construct(string $eventName)
            {
                $this->eventName = $eventName;
            }

            public function getClass(string $name): string
            {
                return "";
            }

            public function getNameByClass(string $class): string
            {
                return $this->eventName;
            }
        };
    }

    private function getNormalizer(): NormalizerInterface
    {
        return
            new Serializer([
                new DateTimeNormalizer([DateTimeNormalizer::FORMAT_KEY => self::DATE_FORMAT]),
                new UuidNormalizer(),
                new PropertyNormalizer(),
            ]);
    }
}

