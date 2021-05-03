<?php

declare(strict_types=1);

namespace Test\Sbooker\DomainEvents\Persistence;

use PHPUnit\Framework\TestCase;
use Sbooker\DomainEvents\Persistence\MapNameGiver;

final class MapNameGiverTest extends TestCase
{
    public function testGetNameByClassSuccess(): void
    {
        $className = 'ClassName';
        $classAlias = 'class.name';
        $otherClassName = 'OtherClassName';
        $otherClassAlias = 'other_class.name';

        $nameGiver = new MapNameGiver([
            $className => $classAlias,
            $otherClassName => $otherClassAlias
        ]);

        $resultClassAlias = $nameGiver->getNameByClass($className);
        $resultOtherClassAlias = $nameGiver->getNameByClass($otherClassName);

        $this->assertEquals($classAlias, $resultClassAlias);
        $this->assertEquals($otherClassAlias, $resultOtherClassAlias);
    }

    public function testGetNameByClassFail(): void
    {
        $className = 'ClassName';
        $classAlias = 'class.name';
        $otherClassName = 'OtherClassName';
        $otherClassAlias = 'other_class.name';

        $nameGiver = new MapNameGiver([
            $className => $classAlias,
            $otherClassName => $otherClassAlias
        ]);

        $this->expectException(\RuntimeException::class);
        $nameGiver->getNameByClass('abracadabra');
    }

    public function testGetClassByName(): void
    {
        $className = 'ClassName';
        $classAlias = 'class.name';
        $otherClassName = 'OtherClassName';
        $otherClassAlias = 'other_class.name';

        $nameGiver = new MapNameGiver([
            $className => $classAlias,
            $otherClassName => $otherClassAlias
        ]);

        $resultClassName = $nameGiver->getClass($classAlias);
        $resultOtherClassName = $nameGiver->getClass($otherClassAlias);

        $this->assertEquals($className, $resultClassName);
        $this->assertEquals($otherClassName, $resultOtherClassName);
    }

    public function testGetClassByNameFail(): void
    {
        $className = 'ClassName';
        $classAlias = 'class.name';
        $otherClassName = 'OtherClassName';
        $otherClassAlias = 'other_class.name';

        $nameGiver = new MapNameGiver([
            $className => $classAlias,
            $otherClassName => $otherClassAlias
        ]);

        $this->expectException(\RuntimeException::class);
        $nameGiver->getClass('abracadabra');
    }
}