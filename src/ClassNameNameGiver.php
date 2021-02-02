<?php

declare(strict_types=1);

namespace Sbooker\DomainEvents\Persistence;

final class ClassNameNameGiver extends EventNameGiver
{
    public function getClass(string $name): string
    {
        return $name;
    }

    public function getNameByClass(string $class): string
    {
        return $class;
    }
}