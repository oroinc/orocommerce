<?php

namespace Oro\Bundle\OrderBundle\Async;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\OrderBundle\Async\Topic\PrecalculateOrderTotalTopic;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Total\TotalHelper;
use Oro\Bundle\PlatformBundle\Manager\OptionalListenerManager;
use Oro\Bundle\TaxBundle\Entity\Tax;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

/**
 * Recalculates order total for all orders and save it in serialized data
 * if it does not match stored order total.
 */
class PrecalculateOrderTotalProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    private const int BATCH_SIZE = 300;

    private const array LISTENERS_TO_BE_DISABLED = [
        'oro_dataaudit.listener.send_changed_entities_to_message_queue',
        'oro_search.index_listener',
        'oro_website.indexation_request_listener'
    ];

    public function __construct(
        private readonly MessageProducerInterface $producer,
        private readonly ManagerRegistry $doctrine,
        private readonly TotalHelper $totalHelper,
        private readonly OptionalListenerManager $optionalListenerManager
    ) {
    }

    #[\Override]
    public static function getSubscribedTopics(): array
    {
        return [PrecalculateOrderTotalTopic::getName()];
    }

    #[\Override]
    public function process(MessageInterface $message, SessionInterface $session): string
    {
        $body = $message->getBody();
        $lastOrderId = $body['lastOrderId'] ?? null;
        if (null !== $lastOrderId) {
            $firstOrderId = $body['firstOrderId'] ?? null;
            if (null === $firstOrderId) {
                $this->schedulePrecalculateOrderTotals($lastOrderId);
            } else {
                $this->precalculateOrderTotals($firstOrderId, $lastOrderId);
            }
        }

        return self::ACK;
    }

    private function schedulePrecalculateOrderTotals(int $lastOrderId): void
    {
        if (!$this->hasTaxRates()) {
            return;
        }

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->doctrine->getManagerForClass(Order::class);
        $rows = $entityManager->createQueryBuilder()
            ->from(Order::class, 'o')
            ->select('o.id')
            ->where('o.id <= :lastOrderId')
            ->setParameter('lastOrderId', $lastOrderId)
            ->orderBy('o.id', 'DESC')
            ->getQuery()
            ->getArrayResult();
        $batchFirstOrderId = null;
        $batchLastOrderId = null;
        $counter = 0;
        foreach ($rows as $row) {
            $counter++;
            $orderId = $row['id'];
            if (null === $batchLastOrderId) {
                $batchLastOrderId = $orderId;
            } else {
                $batchFirstOrderId = $orderId;
                if (($counter % self::BATCH_SIZE) === 0) {
                    $this->sendPrecalculateOrderTotalMessage($batchFirstOrderId, $batchLastOrderId);
                    $batchFirstOrderId = null;
                    $batchLastOrderId = null;
                    $counter = 0;
                }
            }
        }
        if ($counter > 0) {
            $this->sendPrecalculateOrderTotalMessage($batchFirstOrderId ?? $batchLastOrderId, $batchLastOrderId);
        }
    }

    private function sendPrecalculateOrderTotalMessage(int $firstOrderId, int $lastOrderId): void
    {
        $this->producer->send(
            PrecalculateOrderTotalTopic::getName(),
            ['firstOrderId' => $firstOrderId, 'lastOrderId' => $lastOrderId]
        );
    }

    private function precalculateOrderTotals(int $firstOrderId, int $lastOrderId): void
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->doctrine->getManagerForClass(Order::class);
        $orders = $entityManager->createQueryBuilder()
            ->from(Order::class, 'o')
            ->select('o')
            ->where('o.id >= :firstOrderId AND o.id <= :lastOrderId')
            ->setParameter('firstOrderId', $firstOrderId)
            ->setParameter('lastOrderId', $lastOrderId)
            ->getQuery()
            ->getResult();
        $hasChanges = false;
        /** @var Order $order */
        foreach ($orders as $order) {
            $serializedData = $order->getSerializedData();
            if (isset($serializedData['totals'])) {
                // remove data that were added by previous implementation
                unset($serializedData['totals']);
                $order->setSerializedData($serializedData);
                $hasChanges = true;
            }
            if (isset($serializedData['precalculatedTotal'])) {
                continue;
            }
            $recalculatedOrderTotalValue = (float)$this->totalHelper->calculateTotal($order)->getValue();
            if ((float)$order->getTotalObject()->getValue() !== $recalculatedOrderTotalValue) {
                $serializedData['precalculatedTotal'] = $recalculatedOrderTotalValue;
                $order->setSerializedData($serializedData);
                $hasChanges = true;
            }
        }
        if ($hasChanges) {
            $listenersToBeDisabled = array_values(array_diff(
                self::LISTENERS_TO_BE_DISABLED,
                $this->optionalListenerManager->getDisabledListeners()
            ));
            $this->optionalListenerManager->disableListeners($listenersToBeDisabled);
            try {
                $entityManager->flush();
            } finally {
                $this->optionalListenerManager->enableListeners($listenersToBeDisabled);
            }
        }
    }

    private function hasTaxRates(): bool
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->doctrine->getManagerForClass(Tax::class);
        $taxRateCount = $entityManager->createQueryBuilder()
            ->from(Tax::class, 't')
            ->select('COUNT(t)')
            ->getQuery()
            ->getSingleScalarResult();

        return $taxRateCount > 0;
    }
}
