# sbooker/domain-events-persistence

[![Latest Version][badge-release]][release]
[![Software License][badge-license]][license]
[![PHP Version][badge-php]][php]
[![Total Downloads][badge-downloads]][downloads]
[![Build Status](https://travis-ci.com/sbooker/domain-events-persistence.svg?branch=2.x)](https://travis-ci.org/sbooker/domain-events-persistence)
[![codecov](https://codecov.io/gh/sbooker/domain-events-persistence/branch/2.x/graph/badge.svg?token=QB17YFKLPD)](https://codecov.io/gh/sbooker/domain-events-persistence)

Persistence layer for [sbooker/domain-events](https://github.com/sbooker/domain-events)

## Installation
Install via [Composer][composer]:
```bash
composer require sbooker/domain-events-persistence
```

### Usage

```php
<?php

// # Publish events

use Sbooker\DomainEvents\Persistence\ClassNameNameGiver;
use Sbooker\DomainEvents\Persistence\WriteStorage;
use Sbooker\DomainEvents\Persistence\PersistentEvent;
use Sbooker\DomainEvents\Persistence\PersistentPublisher;
use Sbooker\DomainEvents\Persistence\ConsumeStorage;

$eventStorage = new class implements WriteStorage, ConsumeStorage 
{
    public function add(PersistentEvent $event): void {
        // ...
    }
    public function getFirstByPosition(array $eventNames,int $position): ?PersistentEvent {
        // ...
    }
};

$nameGiver = new ClassNameNameGiver();

$normalizer = new class implements \Symfony\Component\Serializer\Normalizer\NormalizerInterface { /* ... */ };

$publisher = new PersistentPublisher($eventStorage, $nameGiver, $normalizer);

// ...

use Sbooker\DomainEvents\DomainEvent;
use Sbooker\DomainEvents\DomainEntity;
use Sbooker\DomainEvents\DominEventCollector;

class SomethingOccurred extends  DomainEvent {}

class Entity implements DomainEntity
{
    use DominEventCollector;
    
    public function doSomething()
    {
        // do
        $this->publish(new SomethingOccurred());
    }
}

$entity = new Entity();
$entity->doSomething();

// Persist events

$entity->dispatchEvents($publisher);

// Subscribe on events

use Sbooker\DomainEvents\Persistence\Consumer;
use Sbooker\DomainEvents\DomainEventSubscriber;
use Sbooker\PersistentPointer\Repository as PersistentPointerRepository;
use Sbooker\TransactionManager\TransactionManager;
use Sbooker\TransactionManager\TransactionHandler;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

$transactionManager = new TransactionManager(
    new class implements TransactionHandler { /* ... */ };
); 

$denormalizer = new class implements DenormalizerInterface { /* ... */ };

$consumer = new Consumer(
    $eventStorage,
    $transactionManager,
    $denormalizer,
    new PersistentPointerRepository(/* ... */),
    $nameGiver,
    new class implements DomainEventSubscriber { /* ... */},
    'subscriber.name'
);

```

## License
See [LICENSE][license] file.

[badge-release]: https://img.shields.io/packagist/v/sbooker/domain-events-persistence.svg?style=flat-square
[badge-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[badge-php]: https://img.shields.io/packagist/php-v/sbooker/domain-events-persistence.svg?style=flat-square
[badge-downloads]: https://img.shields.io/packagist/dt/sbooker/domain-events-persistence.svg?style=flat-square

[release]: https://img.shields.io/packagist/v/sbooker/domain-events-persistence
[license]: https://github.com/sbooker/domain-events-persistence/blob/master/LICENSE
[php]: https://php.net
[downloads]: https://packagist.org/packages/sbooker/domain-events-persistence

[composer]: https://getcomposer.org