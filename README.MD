# sbooker/domain-events-persistence

[![Latest Version][badge-release]][release]
[![Software License][badge-license]][license]
[![PHP Version][badge-php]][php]
[![Total Downloads][badge-downloads]][downloads]

Persistence layer for [sbooker/domain-events](https://github.com/sbooker/domain-events)

## Installation
Install via [Composer][composer]:
```bash
composer require sbooker/domain-events-persistence
```

### Usage

#### Publish event
```php
<?php

use Sbooker\DomainEvents\Persistence\ClassNameNameGiver;
use Sbooker\DomainEvents\Persistence\EventStorage;
use Sbooker\DomainEvents\Persistence\PersistentEvent;
use Sbooker\DomainEvents\Persistence\PersistentPublisher;
use Sbooker\DomainEvents\PublisherContainer;


$eventStorage = new class implements EventStorage {
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

PublisherContainer::instance()->setPublisher($publisher);

use Sbooker\DomainEvents\DomainEvent;
use Sbooker\DomainEvents\EventPublisherContainer;

class SomethingOccurred extends  DomainEvent {}

class SomeAggregateRoot
{
    use EventPublisherContainer;
    
    public function doSomethingOfDomainLogic()
    {
        // do
        $this->publish(new SomethingOccurred());
    }
}

// Subscribe on events

use Sbooker\DomainEvents\Persistence\Consumer;

$transactionManager = new \Sbooker\TransactionManager\TransactionManager(
    new class implements \Sbooker\TransactionManager\TransactionHandler { /* ... */ };
); 

$denormalizer = new class implements \Symfony\Component\Serializer\Normalizer\DenormalizerInterface { /* ... */ };



$consumer = new Consumer(
    $eventStorage,
    $transactionManager,
    $denormalizer,
    new \Sbooker\PersistentPointer\Repository(/* ... */),
    $nameGiver,
    new class implements \Sbooker\DomainEvents\DomainEventSubscriber { /* ... */},
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