<?php

declare(strict_types=1);

namespace Sbooker\DomainEvents\Persistence;

use Ramsey\Uuid\UuidInterface;

interface IdentityStorage
{
    public function findById(UuidInterface $id): ?PersistentEvent;
}