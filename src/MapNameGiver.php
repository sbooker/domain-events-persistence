<?php

declare(strict_types=1);

namespace Sbooker\DomainEvents\Persistence;

final class MapNameGiver extends EventNameGiver
{
    /** @var string[] FQCN => string */
    private array $map;

    public function __construct(array $map)
    {
        $this->map = $map;
    }

    public function getClass(string $name): string
    {
        $reverseMap = array_flip($this->map);

        assert(isset($reverseMap[$name]), 'Event name' . $name . 'mapping not supported');

        return $reverseMap[$name];
    }

    public function getNameByClass(string $class): string
    {
        assert(isset($this->map[$class]), 'Event class' . $class . 'mapping not supported');

        return $this->map[$class];
    }
}