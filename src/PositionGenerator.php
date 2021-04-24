<?php

declare(strict_types=1);

namespace Sbooker\DomainEvents\Persistence;

interface PositionGenerator
{
    public function next(): int;
}