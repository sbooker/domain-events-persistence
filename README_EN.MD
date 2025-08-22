[Читать на русском](README.md)

# Domain Events Persistence Library (`sbooker/domain-events-persistence`)

[![Latest Version][badge-release]][release]
[![Software License][badge-license]][license]
[![PHP Version][badge-php]][php]
[![Total Downloads][badge-downloads]][downloads]
[![Build Status](https://travis-ci.com/sbooker/domain-events-persistence.svg?branch=2.x)](https://travis-ci.org/sbooker/domain-events-persistence)
[![codecov](https://codecov.io/gh/sbooker/domain-events-persistence/branch/2.x/graph/badge.svg?token=QB17YFKLPD)](https://codecov.io/gh/sbooker/domain-events-persistence)

A ready-to-use implementation of the **Transactional Outbox** pattern for the [sbooker/domain-events](https://github.com/sbooker/domain-events) library.

## Library's Purpose

This library solves a critical reliability problem in event-driven systems: **how to guarantee that a domain event will be processed if it was created within a transaction that completed successfully?**

`sbooker/domain-events-persistence` solves this by persisting your domain events to a permanent storage (e.g., the same database) **within the same transaction** as your domain entities. This is achieved through deep integration with [sbooker/transaction-manager](https://github.com/sbooker/transaction-manager).

A separate background process (a consumer) then reliably reads these events and processes them, using [sbooker/persistent-pointer](https://github.com/sbooker/persistent-pointer) to track its progress.

## Key Features

*   **Atomic Persistence:** Events are saved in the same transaction as aggregates. It's guaranteed that either everything is saved, or nothing is.
*   **Fully Automatic Event Persistence:** The `DomainEventPreCommitProcessor` automatically extracts events from your entities right before the transaction commits. **No more need to call `$entity->dispatchEvents()` manually!**
*   **Reliable Consumer:** The built-in `Consumer` mechanism uses `persistent-pointer` to track the position of the last processed event, ensuring "at-least-once" processing.
*   **Ready for Parallel Processing:** The consumer architecture allows you to run multiple workers to process events without a message broker.
*   **Flexible Event Naming:** Supports different event naming strategies (by class name or via a `MapNameGiver`) for long-term stability.

## Installation

```bash
composer require sbooker/domain-events-persistence
```
You will also need implementations for your framework and ORM:
```bash
# Core dependencies
composer require sbooker/domain-events sbooker/transaction-manager sbooker/persistent-pointer

# Implementation for Doctrine
composer require sbooker/doctrine-transaction-handler
```

## Quick Start

### Step 1: The Goal: Clean Application Layer Code

Thanks to full automation, your application layer code becomes extremely simple and knows nothing about events.

```php
// src/UseCase/CreateProduct/Handler.php
final class Handler
{
    private TransactionManager $transactionManager;
    // ...

    public function handle(Command $command): void
    {
        $this->transactionManager->transactional(function () use ($command): void {
            $product = new Product(/* ... */); // An event is created inside
            $this->transactionManager->persist($product);

            // No calls to dispatchEvents()!
            // The processor will do it automatically before the commit.
        });
    }
}
```

### Step 2: Assembling Dependencies (Composition Root)

To achieve this simplicity, you need to assemble all the components once in your DI container.

```php
// bootstrap.php or your DI container

// --- Assuming you already have these services ---
/** @var Sbooker\TransactionManager\TransactionHandler $transactionHandler */
/** @var Symfony\Component\Serializer\SerializerInterface $serializer */
/** @var App\Infrastructure\Security\MyActorStorage $actorStorage */
/** @var Psr\Log\LoggerInterface $logger */
/** @var App\Infrastructure\Persistence\DoctrineConsumeStorage $consumeStorage */

// 1. Choose an event naming strategy
$eventNameGiver = new Sbooker\DomainEvents\Persistence\ClassNameNameGiver();

// 2. Create the Publisher that will save events to the DB
$persistentPublisher = new Sbooker\DomainEvents\Persistence\PersistentPublisher($eventNameGiver, $serializer);

// 3. Create a decorator that adds the Actor to events (optional)
$actorAwarePublisher = new Sbooker\DomainEvents\ActorAwarePublisher($persistentPublisher, $actorStorage);

// 4. Create the processor that automates event persistence
$preCommitProcessor = new Sbooker\DomainEvents\Persistence\DomainEventPreCommitProcessor($actorAwarePublisher);

// 5. Create the TransactionManager and register our processor with it.
// The TransactionManager will automatically call setTransactionManager() on the processor and publisher.
$transactionManager = new Sbooker\TransactionManager\TransactionManager(
    $transactionHandler,
    $preCommitProcessor
);

// 6. Create the factory for consumers
$consumerFactory = new Sbooker\DomainEvents\Persistence\ConsumerFactory(
    $consumeStorage,
    $transactionManager,
    $eventNameGiver,
    $serializer, // The Serializer also acts as a Denormalizer here
    $logger
);

// Now everything is ready to be used!
$handler = new Handler($transactionManager, /* ... */);
```

### Step 3: Creating a Worker-Consumer

Create a console command or a background process that will process events in a loop. It is recommended to use event-loop-based solutions, for example, [sbooker/event-loop-worker](https://github.com/sbooker/event-loop-worker).

```php
// src/Email/Infrastructure/ProcessProductEventsCommand.php
use Sbooker\DomainEvents\Persistence\ConsumerFactory;
use App\Subscribers\EmailNotifier; // Your event subscriber

final class ProcessProductEventsCommand extends Command
{
    private ConsumerFactory $consumerFactory;
    private EmailNotifier $subscriber; // Your handler service

    // ... constructor ...

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Create a consumer for a specific subscriber.
        // The name 'email_notifier' will be used to create a Pointer.
        $consumer = $this->consumerFactory->createBySubscriber(
            'email_notifier',
            $this->subscriber
        );

        $output->writeln('Starting event consumer...');
        while (true) {
            // consume() atomically finds an event, processes it, and saves the new pointer position.
            $processed = $consumer->consume();

            if (!$processed) {
                // If there are no events, wait and try again
                sleep(5);
            }
        }
    }
}
```
## Advanced Usage

### External Position Generation (`PositionGenerator`)

By default, the `position` for a `PersistentEvent` is assumed to be an auto-incrementing field in the database, and the library does not manage its generation. However, there are scenarios where the event sequence must be controlled externally:

1.  Integration with a **legacy system** that already has its own event numbering.
2.  Using a DBMS that **does not support `SEQUENCE`** or has issues with `AUTO_INCREMENT` in clustered configurations (e.g., older versions of MySQL).

The optional `PositionGenerator` interface is designed for these cases.

#### Solution within the ecosystem: `sbooker/persistent-sequences`

To solve this problem without adding external infrastructure (like Redis), the [sbooker/persistent-sequences](https://github.com/sbooker/persistent-sequences) library was created. It implements a persistent, concurrency-safe sequence using a relational DBMS.

#### Step 1: Install and configure `persistent-sequences`

First, install the library:
```bash
composer require sbooker/persistent-sequences
```
Next, create an adapter that bridges `persistent-sequences` with the `PositionGenerator` interface.

```php
// src/Infrastructure/Persistence/SequencePositionGenerator.php
use Sbooker\DomainEvents\Persistence\PositionGenerator;
use Sbooker\PersistentSequences\SequenceGenerator;
use Sbooker\PersistentSequences\Algorithm;

final class SequencePositionGenerator implements PositionGenerator
{
    private const SEQUENCE_NAME = 'domain_events';
    private SequenceGenerator $sequenceGenerator;
    private Algorithm $algorithm;

    public function __construct(SequenceGenerator $sequenceGenerator, Algorithm $algorithm)
    {
        $this->sequenceGenerator = $sequenceGenerator;
        $this->algorithm = $algorithm;
    }

    public function next(): int
    {
        // Get the next value from the named sequence
        return $this->sequenceGenerator->next(self::SEQUENCE_NAME, $this->algorithm);
    }
}
```

#### Step 2: Pass it to the `PersistentPublisher`

Now, when assembling your dependencies, simply pass your generator as the third argument to the `PersistentPublisher`'s constructor.

```php
// bootstrap.php or your DI container

/** @var Sbooker\TransactionManager\TransactionHandler $transactionHandler */
/** @var Symfony\Component\Serializer\SerializerInterface $serializer */
/** @var SequencePositionGenerator $positionGenerator */ // <-- Your generator

// 1. Create the Publisher, passing the PositionGenerator to it
$persistentPublisher = new Sbooker\DomainEvents\Persistence\PersistentPublisher(
    $eventNameGiver,
    $serializer,
    $positionGenerator // <-- Here it is!
);

// ... rest of the setup ...
$transactionManager = new Sbooker\TransactionManager\TransactionManager(
    $transactionHandler,
    $preCommitProcessor
);
```

Now, when persisting an event, `PersistentPublisher` will call your `SequencePositionGenerator` to get and set the position, ensuring a single, monotonically increasing sequence for all events.

## License
See [LICENSE][license] file.

[badge-release]: https://img.shields.io/packagist/v/sbooker/domain-events-persistence.svg?style=flat-square
[badge-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[badge-php]: https://img.shields.io/packagist/php-v/sbooker/domain-events-persistence.svg?style=flat-square
[badge-downloads]: https://img.shields.io/packagist/dt/sbooker/domain-events-persistence.svg?style=flat-square

[release]: https://packagist.org/packages/sbooker/domain-events-persistence
[license]: https://github.com/sbooker/domain-events-persistence/blob/master/LICENSE
[php]: https://php.net
[downloads]: https://packagist.org/packages/sbooker/domain-events-persistence

[composer]: https://getcomposer.org

[badge-release]: https://img.shields.io/packagist/v/sbooker/domain-events-persistence.svg?style=flat-square
[badge-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[badge-php]: https://img.shields.io/packagist/php-v/sbooker/domain-events-persistence.svg?style=flat-square
[badge-downloads]: https://img.shields.io/packagist/dt/sbooker/domain-events-persistence.svg?style=flat-square

[release]: https://packagist.org/packages/sbooker/domain-events-persistence
[license]: https://github.com/sbooker/domain-events-persistence/blob/master/LICENSE
[php]: https://php.net
[downloads]: https://packagist.org/packages/sbooker/domain-events-persistence

[composer]: https://getcomposer.org