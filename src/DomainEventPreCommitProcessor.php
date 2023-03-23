<?php

declare(strict_types=1);

namespace Sbooker\DomainEvents\Persistence;

use Sbooker\DomainEvents\DomainEntity;
use Sbooker\DomainEvents\Publisher;
use Sbooker\TransactionManager\EntityManager;
use Sbooker\TransactionManager\PreCommitEntityProcessor;

final class DomainEventPreCommitProcessor implements PreCommitEntityProcessor
{
    private Publisher $publisher;

    public function __construct(Publisher $publisher)
    {
        $this->publisher = $publisher;
    }

    public function process(EntityManager $em, object $entity): void
    {
        if (!$entity instanceof DomainEntity) {
            return;
        }
        if ($this->publisher instanceof PersistentPublisher) {
            $this->publisher->withEntityManager($em);
        }

        $entity->dispatchEvents($this->publisher);
    }
}