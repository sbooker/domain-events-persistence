<?php

declare(strict_types=1);

namespace Test\Sbooker\DomainEvents\Persistence;

use PHPUnit\Framework\Constraint\Constraint;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Sbooker\DomainEvents\DomainEvent;
use Sbooker\DomainEvents\Persistence\EventNameGiver;
use Sbooker\DomainEvents\Persistence\PersistentEvent;
use Sbooker\DomainEvents\Persistence\PersistentPublisher;
use Sbooker\DomainEvents\Persistence\PositionGenerator;
use Sbooker\DomainEvents\Persistence\WriteStorage;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class PersistentPublisherTest extends TestCase
{
    /**
     * @dataProvider examples
     */
    public function test(?int $postion): void
    {
        $entityId = Uuid::uuid4();
        $event = $this->createEvent($entityId);
        $eventName = 'event.name';
        $normalizedEvent = ['property' => 'value'];
        $persistentEvent = new PersistentEvent(Uuid::uuid4(), $eventName, $event->getOccurredAt(), $event->getEntityId(), $normalizedEvent, $postion);
        $storage = $this->createWriteStorage($persistentEvent);
        $nameGiver = $this->createNameGiver($event, $eventName);
        $normalizer = $this->createNormalizer($event, $normalizedEvent);
        $positionGenerator = $this->createPositionGenerator($postion);

        $publisher = new PersistentPublisher($storage, $nameGiver, $normalizer, $positionGenerator);

        $publisher->publish($event);
    }

    public function examples(): array
    {
        return [
            [ null ],
            [ 123 ],
        ];
    }

    private function createEvent(UuidInterface $id): DomainEvent
    {
        return new class($id) extends DomainEvent {};
    }

    private function createWriteStorage(PersistentEvent $persistentEvent): WriteStorage
    {
        $mock = $this->createMock(WriteStorage::class);
        $mock->expects($this->once())
            ->method('add')
            ->with(new PersistentEventMatcher($persistentEvent));

        return $mock;
    }

    private function createNameGiver(DomainEvent $event, string $eventName): EventNameGiver
    {
        $mock = $this->createMock(EventNameGiver::class);
        $mock->expects($this->once())->method('getNameByClass')->with(get_class($event))->willReturn($eventName);

        return $mock;
    }

    private function createNormalizer(DomainEvent $event, array $normalizedEvent): NormalizerInterface
    {
        $mock = $this->createMock(NormalizerInterface::class);
        $mock->expects($this->once())->method('normalize')->with($event)->willReturn($normalizedEvent);

        return $mock;
    }

    private function createPositionGenerator(?int $number): ?PositionGenerator
    {
        if (null === $number) {
            return null;
        }

        $mock = $this->createMock(PositionGenerator::class);
        $mock->expects($this->once())->method('next')->willReturn($number);

        return $mock;
    }
}

class PersistentEventMatcher extends Constraint
{
    private PersistentEvent $persistentEvent;

    public function __construct(PersistentEvent $persistentEvent)
    {
        $this->persistentEvent = $persistentEvent;
    }

    public function matches($other): bool
    {
        if (!$other instanceof PersistentEvent) {
            return false;
        }

        return
            $this->persistentEvent->getName() === $other->getName()
            &&
            $this->persistentEvent->getPosition() === $other->getPosition()
            &&
            $this->persistentEvent->getPayload() === $other->getPayload()
            &&
            $this->persistentEvent->getEntityId() === $other->getEntityId()
            &&
            $this->persistentEvent->getOccurredAt() === $other->getOccurredAt();
    }

    public function toString(): string
    {
        return 'Persistent events is same';
    }
}
