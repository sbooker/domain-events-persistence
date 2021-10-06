<?php

declare(strict_types=1);

namespace Test\Sbooker\DomainEvents\Persistence;

use Sbooker\DomainEvents\DomainEvent;
use SebastianBergmann\Comparator\ObjectComparator;

final class DomainEventComparator extends ObjectComparator
{
    public function accepts($expected, $actual)
    {
        return $expected instanceof DomainEvent && $actual instanceof DomainEvent;
    }

    protected function toArray($object)
    {
        $array = parent::toArray($object);

        unset($array['occurredAt']);

        return $array;
    }
}