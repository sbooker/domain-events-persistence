<?php

declare(strict_types=1);

namespace Test\Sbooker\DomainEvents\Persistence;

use Gamez\Symfony\Component\Serializer\Normalizer\UuidNormalizer;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Sbooker\DomainEvents\Actor;
use Sbooker\DomainEvents\DomainEvent;
use Sbooker\DomainEvents\DomainEventSubscriber;
use Sbooker\DomainEvents\Persistence\ClassNameNameGiver;
use Sbooker\DomainEvents\Persistence\Consumer;
use Sbooker\DomainEvents\Persistence\ConsumeStorage;
use Sbooker\DomainEvents\Persistence\PersistentEvent;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\Serializer;

class EventConsumerTest extends TestCase
{
    private const DATE_FORMAT = "Y-m-d\TH:i:s.uP";

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
            $this->getEventStorage($persistentEvent),
            $this->getTransactionManager(),
            $this->getDenormalizer(),
            $this->getEmptyPositionStorage(),
            new ClassNameNameGiver(),
            $this->getSubscriber($entityId, $actor, $occurredAt),
            "consumer"
        );

        $result = $consumer->consume();

        $this->assertTrue($result);
    }

    private function getEventStorage(PersistentEvent $event): ConsumeStorage
    {
        return new class ($event) implements ConsumeStorage {
            private PersistentEvent $event;

            public function __construct(PersistentEvent $event) {
                $this->event = $event;
            }

            public function getFirstByPosition(array $eventNames, int $position): ?PersistentEvent
            {
                return $this->event;
            }
        };
    }

    private function getSubscriber(UuidInterface $entityId, ?Actor $actor, \DateTimeImmutable $occurredAt): DomainEventSubscriber
    {
        $mock = $this->createMock(DomainEventSubscriber::class);
        $mock->expects($this->once())->method('handleEvent')->with(
            $this->callback(
                fn (DomainEvent $event): bool =>
                    $event instanceof TestDomainEvent
                    && $entityId->equals($event->getEntityId())
                    && null === $actor ? null === $event->getActor() : $actor->getId()->equals($event->getActor()->getId())
                    && $occurredAt->format(self::DATE_FORMAT) === $event->getOccurredAt()->format(self::DATE_FORMAT)
            )
        );

        return $mock;
    }

    private function getDenormalizer(): DenormalizerInterface
    {
        return new Serializer([
            new DateTimeNormalizer([DateTimeNormalizer::FORMAT_KEY => self::DATE_FORMAT]),
            new UuidNormalizer(),
            new PropertyNormalizer(
                null,
                null,
                new PropertyInfoExtractor(
                    [],
                    [
                        new PhpDocExtractor(),
                        new ReflectionExtractor(),
                    ]
                )
            ),
        ]);
    }
}