[Read in English](README.EN.MD)

# Domain Events Persistence Library (`sbooker/domain-events-persistence`)

[![Latest Version][badge-release]][release]
[![Software License][badge-license]][license]
[![PHP Version][badge-php]][php]
[![Total Downloads][badge-downloads]][downloads]
[![Build Status](https://travis-ci.com/sbooker/domain-events-persistence.svg?branch=2.x)](https://travis-ci.org/sbooker/domain-events-persistence)
[![codecov](https://codecov.io/gh/sbooker/domain-events-persistence/branch/2.x/graph/badge.svg?token=QB17YFKLPD)](https://codecov.io/gh/sbooker/domain-events-persistence)

Готовая реализация паттерна **Transactional Outbox** для библиотеки [sbooker/domain-events](https://github.com/sbooker/domain-events).

## Назначение библиотеки

Эта библиотека решает проблему надежности в системах, управляемых событиями: **как гарантировать, что доменное событие будет обработано, если оно было создано в рамках транзакции, которая успешно завершилась?**

`sbooker/domain-events-persistence` решает эту проблему, сохраняя ваши доменные события в постоянное хранилище (например, в ту же базу данных) **внутри той же транзакции**, что и ваши доменные сущности. Это достигается благодаря глубокой интеграции с [sbooker/transaction-manager](https://github.com/sbooker/transaction-manager).

Затем отдельный фоновый процесс (консьюмер) считывает эти события и обрабатывает их, используя [sbooker/persistent-pointer](https://github.com/sbooker/persistent-pointer) для отслеживания прогресса.

## Ключевые особенности

*   **Атомарное сохранение:** События сохраняются в той же транзакции, что и агрегаты. Гарантируется, что либо сохраняется всё, либо ничего.
*   **Полностью автоматическое созранение событий:** `DomainEventPreCommitProcessor` автоматически извлекает события из ваших сущностей прямо перед коммитом транзакции. **Больше не нужно вызывать `$entity->dispatchEvents()` вручную!**
*   **Надежный консьюмер:** Встроенный механизм `Consumer` использует `persistent-pointer` для отслеживания позиции последнего обработанного события, что гарантирует обработку "хотя бы один раз" (at-least-once).
*   **Готовность к параллельной обработке:** Архитектура консьюмеров позволяет запускать несколько воркеров для обработки событий без брокера сообщений.
*   **Гибкое именование событий:** Поддерживаются разные стратегии именования событий (по имени класса или через карту `MapNameGiver`) для долгосрочной стабильности.

## Установка

```bash
composer require sbooker/domain-events-persistence
```
Вам также понадобятся реализации для ваших фреймворков и ORM:
```bash
# Основные зависимости
composer require sbooker/domain-events sbooker/transaction-manager sbooker/persistent-pointer

# Реализация для Doctrine
composer require sbooker/doctrine-transaction-handler
```

## Быстрый старт

### Шаг 1: Конечная цель: чистый код слоя приложения

Благодаря полной автоматизации, ваш код в слое проложения становится предельно простым и не знает ничего о событиях.

```php
// src/UseCase/CreateProduct/Handler.php
final class Handler
{
    private TransactionManager $transactionManager;
    // ...

    public function handle(Command $command): void
    {
        $this->transactionManager->transactional(function () use ($command): void {
            $product = new Product(/* ... */); // Внутри создается событие
            $this->transactionManager->persist($product);

            // Никаких вызовов dispatchEvents()!
            // Процессор сделает это автоматически перед коммитом.
        });
    }
}
```

### Шаг 2: Сборка зависимостей (Composition Root)

Чтобы достичь такой простоты, вам нужно один раз собрать все компоненты вместе в вашем DI-контейнере.

```php
// bootstrap.php или ваш DI-контейнер

// --- Предполагается, что у вас уже есть эти сервисы ---
/** @var Sbooker\TransactionManager\TransactionHandler $transactionHandler */
/** @var Symfony\Component\Serializer\SerializerInterface $serializer */
/** @var App\Infrastructure\Security\MyActorStorage $actorStorage */
/** @var Psr\Log\LoggerInterface $logger */
/** @var App\Infrastructure\Persistence\DoctrineConsumeStorage $consumeStorage */

// 1. Выбираем стратегию именования событий
$eventNameGiver = new Sbooker\DomainEvents\Persistence\ClassNameNameGiver();

// 2. Создаем Publisher, который будет сохранять события в БД
$persistentPublisher = new Sbooker\DomainEvents\Persistence\PersistentPublisher($eventNameGiver, $serializer);

// 3. Создаем декоратор, который добавляет Actor'а к событиям (опционально)
$actorAwarePublisher = new Sbooker\DomainEvents\ActorAwarePublisher($persistentPublisher, $actorStorage);

// 4. Создаем процессор, автоматизирующий сохранение событий
$preCommitProcessor = new Sbooker\DomainEvents\Persistence\DomainEventPreCommitProcessor($actorAwarePublisher);

// 5. Создаем TransactionManager и регистрируем в нем наш процессор.
// TransactionManager автоматически вызовет setTransactionManager() на процессоре и паблишере.
$transactionManager = new Sbooker\TransactionManager\TransactionManager(
    $transactionHandler,
    $preCommitProcessor
);

// 6. Создаем фабрику для консьюмеров
$consumerFactory = new Sbooker\DomainEvents\Persistence\ConsumerFactory(
    $consumeStorage,
    $transactionManager,
    $eventNameGiver,
    $serializer, // Serializer здесь выступает как Denormalizer
    $logger
);

// Теперь все готово для использования!
$handler = new Handler($transactionManager, /* ... */);
```

### Шаг 3: Создание воркера-консьюмера

Создайте консольную команду или фоновый процесс (рекомендуется) на базе решений на event loop, например на [sbooker/event-loop-worker](https://github.com/sbooker/event-loop-worker), который будет в цикле обрабатывать события.

```php
// src/Email/Infrastructure/ProcessProductEventsCommand.php
use Sbooker\DomainEvents\Persistence\ConsumerFactory;
use App\Subscribers\EmailNotifier; // Ваш подписчик на события

final class ProcessProductEventsCommand extends Command
{
    private ConsumerFactory $consumerFactory;
    private EmailNotifier $subscriber; // Ваш сервис-обработчик

    // ... constructor ...

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Создаем консьюмер для конкретного подписчика.
        // Имя 'email_notifier' будет использоваться для создания Pointer'а.
        $consumer = $this->consumerFactory->createBySubscriber(
            'email_notifier',
            $this->subscriber
        );

        $output->writeln('Starting event consumer...');
        while (true) {
            // consume() атомарно находит событие, обрабатывает его и сохраняет новую позицию указателя.
            $processed = $consumer->consume();

            if (!$processed) {
                // Если событий нет, ждем и повторяем
                sleep(5);
            }
        }
    }
}
```
## Продвинутое использование

### Внешняя генерация позиций (`PositionGenerator`)

По умолчанию, `position` для `PersistentEvent` предполагается автоинкрементным полем в базе данных, и библиотека не управляет его генерацией. Однако существуют сценарии, когда последовательность событий должна управляться извне:

1.  Интеграция с **legacy-системой**, у которой уже есть своя нумерация событий.
2.  Использование СУБД, которые **не поддерживают `SEQUENCE`** или имеют проблемы с `AUTO_INCREMENT` в кластерных конфигурациях (например, старые версии MySQL).

Для этих случаев предназначен необязательный интерфейс `PositionGenerator`.

#### Решение в рамках экосистемы: `sbooker/persistent-sequences`

Для решения этой задачи без привлечения внешней инфраструктуры (вроде Redis) была создана библиотека [sbooker/persistent-sequences](https://github.com/sbooker/persistent-sequences). Она реализует персистентную, конкурентно-безопасную последовательность средствами реляционной СУБД.

#### Шаг 1: Установите и настройте `persistent-sequences`

Сначала установите библиотеку:
```bash
composer require sbooker/persistent-sequences
```
Затем создайте адаптер, который будет связывать `persistent-sequences` с интерфейсом `PositionGenerator`.

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
    }

    public function next(): int
    {
        // Получаем следующее значение из именованной последовательности
        return $this->sequenceGenerator->next(self::SEQUENCE_NAME, $this->algorithm);
    }
}
```

#### Шаг 2: Передайте его в `PersistentPublisher`

Теперь при сборке зависимостей просто передайте ваш генератор третьим аргументом в конструктор `PersistentPublisher`.

```php
// bootstrap.php или ваш DI-контейнер

/** @var Sbooker\TransactionManager\TransactionHandler $transactionHandler */
/** @var Symfony\Component\Serializer\SerializerInterface $serializer */
/** @var SequencePositionGenerator $positionGenerator */ // <-- Ваш генератор

// 1. Создаем Publisher, передавая в него PositionGenerator
$persistentPublisher = new Sbooker\DomainEvents\Persistence\PersistentPublisher(
    $eventNameGiver,
    $serializer,
    $positionGenerator // <-- Вот он!
);

// ... остальная сборка ...
$transactionManager = new Sbooker\TransactionManager\TransactionManager(
    $transactionHandler,
    $preCommitProcessor
);
```

Теперь при сохранении события `PersistentPublisher` будет вызывать ваш `SequencePositionGenerator` для получения и установки позиции, обеспечивая единую, монотонно возрастающую последовательность для всех событий.

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