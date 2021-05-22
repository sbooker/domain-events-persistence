<?php

declare(strict_types=1);

namespace Test\Sbooker\DomainEvents\Persistence;

use Ramsey\Uuid\Uuid;
use Sbooker\DomainEvents\Actor;
use Sbooker\DomainEvents\DomainEvent;
use Sbooker\PersistentPointer\Pointer;
use Sbooker\PersistentPointer\PointerStorage;
use Sbooker\PersistentPointer\Repository;
use Sbooker\TransactionManager\TransactionHandler;
use Sbooker\TransactionManager\TransactionManager;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected const DATE_FORMAT = "Y-m-d\TH:i:s.uP";

    private const ACTOR_ID = "9a33a366-0314-4ed2-b8fe-86f137bc10cf";

    final protected function getTransactionManager(): TransactionManager
    {
        return new TransactionManager(
            new class implements TransactionHandler {
                public function begin(): void { }
                public function commit(): void { }
                public function rollBack(): void { }
                public function clear(): void { }
            }
        );
    }

    final protected function getEmptyPositionStorage(): Repository
    {
        return new Repository(
            new class implements PointerStorage {

                public function add(Pointer $pointer): void { }

                public function getAndLock(string $name): ?Pointer
                {
                    return null;
                }
            }
        );
    }

    final public function actorExamples(): array
    {
        return [
            [ new Actor(Uuid::fromString(self::ACTOR_ID)), ["actor" => ["id" => self::ACTOR_ID]] ],
            [ null, ["actor" => null] ],
        ];
    }
}

final class TestDomainEvent extends DomainEvent
{

}
