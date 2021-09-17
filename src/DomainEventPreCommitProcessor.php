<?php

declare(strict_types=1);

namespace Sbooker\DomainEvents\Persistence;

use Sbooker\DomainEvents\DomainEntity;
use Sbooker\DomainEvents\Publisher;
use Sbooker\TransactionManager\PreCommitEntityProcessor;
use Sbooker\TransactionManager\TransactionManager;
use Sbooker\TransactionManager\TransactionManagerAware;

final class DomainEventPreCommitProcessor implements PreCommitEntityProcessor, TransactionManagerAware
{
    private Publisher $publisher;

    public function __construct(Publisher $publisher)
    {
        $this->publisher = $publisher;
    }

    public function process(object $entity): void
    {
        if (!$entity instanceof DomainEntity) {
            return;
        }

        $entity->dispatchEvents($this->publisher);
    }

    public function setTransactionManager(TransactionManager $transactionManager): void
    {
        if ($this->publisher instanceof TransactionManagerAware) {
            $this->publisher->setTransactionManager($transactionManager);
        }
    }
}