<?php

declare(strict_types=1);

namespace Test\Sbooker\DomainEvents\Persistence;

use Sbooker\DomainEvents\DomainEventSubscriber;
use Sbooker\DomainEvents\Persistence\ClassNameNameGiver;
use Sbooker\DomainEvents\Persistence\Consumer;
use Sbooker\DomainEvents\Persistence\ConsumerFactory;
use Sbooker\DomainEvents\Persistence\ConsumeStorage;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class ConsumerFactoryTest extends TestCase
{
    public function test(): void
    {
        $factory = new ConsumerFactory(
            $this->getConsumeStorage(),
            $this->getTransactionManager(),
            new ClassNameNameGiver(),
            $this->getDenormalizer()
        );

        $consumer = $factory->createBySubscriber('sunscriber.name', $this->getSubscriber());

        $this->assertInstanceOf(Consumer::class, $consumer);
    }

    private function getConsumeStorage(): ConsumeStorage
    {
        $mock = $this->createMock(ConsumeStorage::class);
        $mock->expects($this->never())->method('getFirstByPosition');

        return $mock;
    }

    private function getDenormalizer(): DenormalizerInterface
    {
        $mock = $this->createMock(DenormalizerInterface::class);
        $mock->expects($this->never())->method('denormalize');
        $mock->expects($this->never())->method('supportsDenormalization');

        return $mock;
    }

    private function getSubscriber(): DomainEventSubscriber
    {
        $mock = $this->createMock(DomainEventSubscriber::class);
        $mock->expects($this->never())->method('handleEvent');
        $mock->expects($this->never())->method('getListenedEventClasses');

        return $mock;
    }
}