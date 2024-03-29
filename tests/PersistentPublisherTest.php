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
use Sbooker\TransactionManager\TransactionHandler;
use Sbooker\TransactionManager\TransactionManager;
use Sbooker\TransactionManager\TransactionManagerAware;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class PersistentPublisherTest extends TestCase
{
    /**
     * @dataProvider positionGeneratorExamples
     */
    public function testSetTransactionManager(PositionGenerator $positionGenerator): void
    {
        $entityId = Uuid::uuid4();
        $event = $this->createEvent($entityId);
        $eventName = 'event.name';
        $normalizedEvent = ['property' => 'value'];
        $persistentEvent = new PersistentEvent(Uuid::uuid4(), $eventName, $event->getOccurredAt(), $event->getEntityId(), $normalizedEvent, 1);
        $nameGiver = $this->createNameGiver($event, $eventName, 0);
        $normalizer = $this->createNormalizer($event, $normalizedEvent, 0);
        $transactionManager = new TransactionManager($this->createNeverCalledTransactionHandler());

        $publisher = new PersistentPublisher($nameGiver, $normalizer, $positionGenerator);
        $publisher->setTransactionManager($transactionManager);
    }

    public function positionGeneratorExamples(): array
    {
        return [
            [ $this->createPositionGenerator(1, 0) ],
            [ $this->createTransactionManagerAwarePositionGenerator() ],
        ];
    }

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
        $nameGiver = $this->createNameGiver($event, $eventName);
        $normalizer = $this->createNormalizer($event, $normalizedEvent);
        $positionGenerator = $this->createPositionGenerator($postion);
        $transactionManager = $this->createTransactionManager($persistentEvent);

        $publisher = new PersistentPublisher($nameGiver, $normalizer, $positionGenerator);
        $publisher->setTransactionManager($transactionManager);

        $transactionManager->transactional(
            fn() => $publisher->publish($event)
        );
    }

    /**
     * @dataProvider examples
     */
    public function testTransactionManagerNotSets(?int $postion): void
    {
        $entityId = Uuid::uuid4();
        $event = $this->createEvent($entityId);
        $eventName = 'event.name';
        $normalizedEvent = ['property' => 'value'];
        $nameGiver = $this->createNameGiver($event, $eventName, 0);
        $normalizer = $this->createNormalizer($event, $normalizedEvent, 0);
        $positionGenerator = $this->createPositionGenerator($postion, 0);
        $transactionManager = new TransactionManager($this->getFailedTransactionHandler());

        $publisher = new PersistentPublisher($nameGiver, $normalizer, $positionGenerator);

        $this->expectException(\RuntimeException::class);
        $transactionManager->transactional(
            fn() => $publisher->publish($event)
        );
    }

    private function createTransactionManager(PersistentEvent $persistentEvent): TransactionManager
    {
        return new TransactionManager($this->getTransactionHandler($persistentEvent));
    }

    protected function getTransactionHandler(PersistentEvent $persistentEvent): TransactionHandler
    {
        $mock = $this->createMock(TransactionHandler::class);
        $mock->expects($this->once())->method('begin');
        $mock->expects($this->once())->method('persist')->with(new PersistentEventMatcher($persistentEvent));
        $mock->expects($this->once())->method('commit');
        $mock->expects($this->never())->method('rollback');

        return $mock;
    }

    public function getFailedTransactionHandler(): TransactionHandler
    {
        $mock = $this->createMock(TransactionHandler::class);
        $mock->expects($this->once())->method('begin');
        $mock->expects($this->never())->method('persist');
        $mock->expects($this->never())->method('commit');
        $mock->expects($this->once())->method('rollback');

        return $mock;
    }

    private function createNeverCalledTransactionHandler(): TransactionHandler
    {
        $mock = $this->createMock(TransactionHandler::class);
        $mock->expects($this->never())->method('begin');
        $mock->expects($this->never())->method('persist');
        $mock->expects($this->never())->method('commit');
        $mock->expects($this->never())->method('rollback');
        $mock->expects($this->never())->method('clear');
        $mock->expects($this->never())->method('getLocked');

        return $mock;
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

    private function createNameGiver(DomainEvent $event, string $eventName, int $count = 1): EventNameGiver
    {
        $mock = $this->createMock(EventNameGiver::class);
        $mock->expects($this->exactly($count))->method('getNameByClass')->with(get_class($event))->willReturn($eventName);

        return $mock;
    }

    private function createNormalizer(DomainEvent $event, array $normalizedEvent, int $count = 1): NormalizerInterface
    {
        $mock = $this->createMock(NormalizerInterface::class);
        $mock->expects($this->exactly($count))->method('normalize')->with($event)->willReturn($normalizedEvent);

        return $mock;
    }

    private function createPositionGenerator(?int $number, int $count = 1): ?PositionGenerator
    {
        if (null === $number) {
            return null;
        }

        $mock = $this->createMock(PositionGenerator::class);
        $mock->expects($this->exactly($count))->method('next')->willReturn($number);

        return $mock;
    }

    private function createTransactionManagerAwarePositionGenerator(): TransactionManagerAwarePositionGenerator
    {
        $mock = $this->createMock(TransactionManagerAwarePositionGenerator::class);
        $mock->expects($this->never())->method('next');
        $mock->expects($this->once())->method('setTransactionManager');

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

    protected function additionalFailureDescription($other): string
    {
        return parent::additionalFailureDescription($other) . $this->buildFailureDescription($other);
    }

    private function buildFailureDescription($other): string
    {
        if (!$other instanceof PersistentEvent) {
            return 'Expected ' . PersistentEvent::class . ', ' . var_export($other, true) . 'given.';
        }
        $errors = [];
        if ($this->persistentEvent->getName() !== $other->getName()) {
            $errors[] = $this->printError('name', $this->persistentEvent->getName(), $other->getName());
        }
        if ($this->persistentEvent->getPosition() !== $other->getPosition()) {
            $errors[] = $this->printError('position', (string)$this->persistentEvent->getPosition(), (string)$other->getPosition());
        }
        if ($this->persistentEvent->getPayload() !== $other->getPayload()) {
            $errors[] = $this->printError(
                'payload',
                var_export($this->persistentEvent->getPayload(), true),
                var_export($other->getPayload(), true)
            );
        }
        if (!$this->persistentEvent->getEntityId()->equals($other->getEntityId())) {
            $errors[] = $this->printError(
                'entityId',
                $this->persistentEvent->getEntityId()->toString(),
                $other->getEntityId()->toString(),
            );
        }
        if ($this->persistentEvent->getOccurredAt() !== $other->getOccurredAt()) {
            $errors[] = $this->printError(
                'occurredAt',
                var_export($this->persistentEvent->getOccurredAt(), true),
                var_export($other->getOccurredAt(), true)
            );
        }
        return implode(' ', $errors);
    }

    private function printError(string $param, string $expected, string $given): string
    {
        return "Expected $param {$expected}, $given given.";
    }

    public function toString(): string
    {
        return 'Persistent events is same';
    }
}

interface TransactionManagerAwarePositionGenerator extends PositionGenerator, TransactionManagerAware {}
