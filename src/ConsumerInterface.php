<?php

declare(strict_types=1);

namespace Sbooker\DomainEvents\Persistence;

interface ConsumerInterface
{
    public function consume(): bool;
}