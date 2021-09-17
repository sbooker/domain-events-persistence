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

#### Publish event
See [sbooker/domain-events README](https://github.com/sbooker/domain-events/blob/master/README.MD).
Also you need configure DomainEventPreCommitProcessor 
```php
$transactionManager = new TransactionManager(
    new class implements TransactionHandler { ... },
    new DomainEventPreCommitProcessor(
        new PersistentPublisher(...)
    )
);
```

#### Subscribe on events

```php
<?php

use Sbooker\DomainEvents\Persistence\ClassNameNameGiver;
use Sbooker\DomainEvents\Persistence\PersistentEvent;


$eventStorage = new class implements \Sbooker\DomainEvents\Persistence\ConsumeStorage {
    public function getFirstByPosition(array $eventNames,int $position): ?PersistentEvent {
        // ...
    }
};

$nameGiver = new ClassNameNameGiver();

$normalizer = new class implements \Symfony\Component\Serializer\Normalizer\NormalizerInterface { /* ... */ };

use Sbooker\DomainEvents\Persistence\Consumer;

$transactionManager = new \Sbooker\TransactionManager\TransactionManager(
    new class implements \Sbooker\TransactionManager\TransactionHandler { /* ... */ };
); 

$denormalizer = new class implements \Symfony\Component\Serializer\Normalizer\DenormalizerInterface { /* ... */ };

// Own custom event handler
$eventHandler = new class implements \Sbooker\DomainEvents\Persistence\PersistentEventHandler {
    public function handle(PersistentEvent $event) : void
    {
        // Handle Event
    }
    public function getHandledEventNames() : array
    {
        return [ /* event names as Name Giver returns */ ];
    }
};

// Or with Subscriber 

$subscriber = new class implements \Sbooker\DomainEvents\DomainEventSubscriber {
        public function getListenedEventClasses() : array {
            return [ \Sbooker\DomainEvents\DomainEvent::class ];
        }
        
        public function handleEvent(\Sbooker\DomainEvents\DomainEvent $event) : void {
            // haddle domain event
        }
    };

$eventHandler = new \Sbooker\DomainEvents\Persistence\ConsumerSubscriberBridge(
    $nameGiver,
    $denormalizer, 
    $subscriber
);

$consumer = new Consumer(
    $eventStorage,
    $transactionManager,
    new \Sbooker\PersistentPointer\Repository(/* ... */),
    $eventHandler,
    'subscriber.name'
);

// Or all with factory

$factory = new \Sbooker\DomainEvents\Persistence\ConsumerFactory(
        $eventStorage, 
        $transactionManager,
        new \Sbooker\PersistentPointer\Repository(/* ... */),
        $nameGiver,
        $denormalizer 
    );

$consumer = $factory->createBySubscriber('subscriber.name', $subscriber);

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