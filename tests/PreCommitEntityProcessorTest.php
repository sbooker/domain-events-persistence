<?php

declare(strict_types=1);

namespace Test\Sbooker\DomainEvents\Persistence;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Sbooker\DomainEvents\DomainEntity;
use Sbooker\DomainEvents\DomainEvent;
use Sbooker\DomainEvents\DomainEventCollector;
use Sbooker\DomainEvents\Persistence\DomainEventPreCommitProcessor;
use Sbooker\DomainEvents\Publisher;
use Sbooker\TransactionManager\TransactionHandler;
use Sbooker\TransactionManager\TransactionManager;
use Sbooker\TransactionManager\TransactionManagerAware;
use SebastianBergmann\Comparator\Factory;

final class PreCommitEntityProcessorTest extends TestCase
{
    public function testPublishEvent(): void
    {
        $aggregateRoot = $this->createAggregateRoot();
        $events = [ new AggregateRootSomethingDoing($aggregateRoot->getId()) ];
        $transactionManager = $this->createTransactionManager($aggregateRoot, $events);

        $transactionManager->transactional(function () use ($transactionManager, $aggregateRoot): void {
            $entity = $transactionManager->getLocked(AggregateRoot::class, $aggregateRoot->getId());
            $entity->doSomething();
        });
    }

    public function testPublishNestedEvents(): void
    {
        $aggregateRoot = $this->createAggregateRoot();
        $events = [ new AggregateRootSomethingDoing($aggregateRoot->getId()), new EntitySomethingDoing($aggregateRoot->getEntityId()) ];
        $transactionManager = $this->createTransactionManager($aggregateRoot, $events);

        $transactionManager->transactional(function () use ($transactionManager, $aggregateRoot): void {
            /** @var AggregateRoot $entity */
            $entity = $transactionManager->getLocked(AggregateRoot::class, $aggregateRoot->getId());
            $entity->doSomethingWithEntity();
        });
    }

    protected function setUp(): void
    {
        parent::setUp();
        Factory::getInstance()->register(new DomainEventComparator());
    }

    private function createTransactionManager(AggregateRoot $aggregateRoot, array $events): TransactionManager
    {
        $preCommitProcessor = new DomainEventPreCommitProcessor($this->createPublisher());

        $transactionManager = new TransactionManager(
            $this->createTransactionHandler($aggregateRoot, $events),
            $preCommitProcessor
        );

        $preCommitProcessor->setTransactionManager($transactionManager);

        return $transactionManager;
    }

    private function createTransactionHandler(AggregateRoot $aggregateRoot, array $events): TransactionHandler
    {
        $mock = $this->createMock(TransactionHandler::class);

        $mock->expects($this->once())->method('begin');
        $mock->expects($this->exactly(count($events)))
            ->method('persist')
            ->withConsecutive(...array_map(fn(DomainEvent $event): array => [ $event ], $events));
        $mock->expects($this->never())->method('rollback');
        $mock->expects($this->once())->method('getLocked')
            ->with(AggregateRoot::class, $aggregateRoot->getId())
            ->willReturn($aggregateRoot);

        $mock->expects($this->once())->method('commit')->with(array_merge($events, [$aggregateRoot],));

        return $mock;
    }

    private function createPublisher(): Publisher
    {
        return new class implements Publisher, TransactionManagerAware
        {
            private TransactionManager $transactionManager;

            public function publish(DomainEvent $event): void
            {
                $this->transactionManager->persist($event);
            }

            public function setTransactionManager(TransactionManager $transactionManager): void
            {
                $this->transactionManager = $transactionManager;
            }
        };
    }

    private function createAggregateRoot(): AggregateRoot
    {
        return new AggregateRoot(Uuid::uuid4(), new Entity(Uuid::uuid4()));
    }
}

final class AggregateRoot implements DomainEntity
{
    use DomainEventCollector { dispatchEvents as doDispatchEvents; }

    private UuidInterface $id;
    private Entity $entity;

    public function __construct(UuidInterface $id, Entity $entity)
    {
        $this->id = $id;
        $this->entity = $entity;
    }

    public function doSomething(): void
    {
        $this->publish(new AggregateRootSomethingDoing($this->id));
    }

    public function doSomethingWithEntity(): void
    {
        $this->entity->doSomething();
        $this->publish(new AggregateRootSomethingDoing($this->id));
    }

    public function dispatchEvents(Publisher $publisher): void
    {
        $this->doDispatchEvents($publisher);
        $this->entity->dispatchEvents($publisher);
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getEntityId(): UuidInterface
    {
        return $this->entity->getId();
    }
}

final class AggregateRootSomethingDoing extends DomainEvent
{

}

final class Entity implements DomainEntity {
    use DomainEventCollector;
    private UuidInterface $id;

    public function __construct(UuidInterface $id)
    {
        $this->id = $id;
    }

    public function doSomething(): void
    {
        $this->publish(new EntitySomethingDoing($this->id));
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }
}

final class EntitySomethingDoing extends DomainEvent
{

}



