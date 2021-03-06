<?php

declare(strict_types=1);

namespace Sbooker\DomainEvents\Persistence;

interface CleanExpiredStorage
{
    /**
     * Remove old events
     *
     * @param int $retentionPeriod in seconds
     */
    public function removeExpired(int $retentionPeriod): void;
}