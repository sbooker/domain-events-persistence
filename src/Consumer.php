<?php

declare(strict_types=1);

namespace Sbooker\DomainEvents\Persistence;

use Psr\Log\LoggerInterface;
use Sbooker\PersistentPointer\Pointer;
use Sbooker\TransactionManager\TransactionManager;

final class Consumer
{
    private ConsumeStorage $eventStorage;
    private TransactionManager $transactionManager;
    private PersistentEventHandler $handler;
    private string $name;
    private ?LoggerInterface $logger;

    public function __construct(
        ConsumeStorage $eventStorage,
        TransactionManager $transactionManager,
        PersistentEventHandler $handler,
        string $name,
        ?LoggerInterface $logger = null
    ) {
        $this->eventStorage = $eventStorage;
        $this->transactionManager = $transactionManager;
        $this->handler = $handler;
        $this->name = $name;
        $this->logger = $logger;
    }

    /**
     * @throws \Throwable
     */
    public function consume(): bool
    {
        return
            $this->transactionManager->transactional(function (): bool {
                $position = $this->getPosition();

                $this->log('Consume event after position #{p}', ['p' => $position->getValue()]);

                $event = $this->eventStorage->getFirstByPosition($this->getConsumableEventNames(),  $position->getValue());

                if (!$event) {
                    $this->log('No events found');
                    return false;
                }

                $this->log('Consume event at position #{p}', ['p' => $event->getPosition()]);

                $this->handleEvent($event);

                $newPosition = $event->getPosition();
                if (null === $newPosition) {
                    throw new \RuntimeException('Event must have position to consume');
                }

                $position->increaseTo($newPosition);

                return true;
            });
    }

    public function getConsumableEventNames(): array
    {
        return $this->handler->getHandledEventNames();
    }

    private function getName(): string
    {
        return $this->name;
    }

    private function getPosition(): Pointer
    {
        $position = $this->transactionManager->getLocked(Pointer::class, $this->getName());
        if (null === $position) {
            $position = new Pointer($this->getName());
            $this->transactionManager->persist($position);
        }

        return $position;
    }

    /**
     * @throws \Exception
     */
    private function handleEvent(PersistentEvent $event): void
    {
        $this->handler->handle($event);
    }

    private function log(string $message, array $context = []): void
    {
        if (!$this->logger) {
            return;
        }

        $this->logger->debug($message, $context);
    }
}