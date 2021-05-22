<?php

declare(strict_types=1);

namespace Sbooker\DomainEvents\Persistence;

use Psr\Log\LoggerInterface;
use Sbooker\PersistentPointer;
use Sbooker\TransactionManager\TransactionManager;

final class Consumer
{
    private ConsumeStorage $eventStorage;

    private TransactionManager $transactionManager;

    private PersistentPointer\Repository $positionStorage;

    private PersistentEventHandler $handler;

    private string $name;

    private ?LoggerInterface $logger;

    public function __construct(
        ConsumeStorage $eventStorage,
        TransactionManager $transactionManager,
        PersistentPointer\Repository $positionStorage,
        PersistentEventHandler $handler,
        string $name,
        ?LoggerInterface $logger = null
    ) {
        $this->eventStorage = $eventStorage;
        $this->transactionManager = $transactionManager;
        $this->positionStorage = $positionStorage;
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
                $position = $this->positionStorage->getLocked($this->getName());;

                $this->debug('Consume event after position #{p}', ['p' => $position->getValue()]);

                $event = $this->eventStorage->getFirstByPosition($this->getConsumableEventNames(),  $position->getValue());

                if (!$event) {
                    $this->debug('No events found');
                    return false;
                }

                $this->debug('Consume event at position #{p}', ['p' => $event->getPosition()]);

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

    /**
     * @throws \Exception
     */
    private function handleEvent(PersistentEvent $event): void
    {
        $this->handler->handle($event);
    }

    private function debug(string $message, array $context = []): void
    {
        if (!$this->logger) {
            return;
        }

        $this->logger->debug($message, $context);
    }
}