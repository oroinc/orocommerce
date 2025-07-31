<?php

namespace Oro\Bundle\OrderBundle\Api\Processor;

use Doctrine\ORM\Query;
use Oro\Bundle\ApiBundle\Normalizer\ObjectNormalizer;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Processor\SharedDataAwareContextInterface;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\OrderBundle\Api\Repository\OrderSubtotalRepository;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Computes a value of "orderSubtotals" field for Order entity.
 */
class ComputeOrderSubtotals implements ProcessorInterface
{
    private const string ORDER_SUBTOTALS_FIELD_NAME = 'orderSubtotals';
    private const string ORDER_SUBTOTALS = 'order_subtotals';

    public function __construct(
        private OrderSubtotalRepository $orderSubtotalRepository,
        private DoctrineHelper $doctrineHelper,
        private ObjectNormalizer $objectNormalizer
    ) {
    }

    /**
     * Adds subtotals for the given order to the list of preloaded subtotals.
     * This list is stored in shared data.
     */
    public static function addOrderSubtotal(
        SharedDataAwareContextInterface $context,
        int $orderId,
        array $orderSubtotals
    ): void {
        $sharedData = $context->getSharedData();
        $allOrderSubtotals = $sharedData->get(self::ORDER_SUBTOTALS) ?? [];
        $allOrderSubtotals[$orderId] = $orderSubtotals;
        $sharedData->set(self::ORDER_SUBTOTALS, $allOrderSubtotals);
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        if (!$context->isFieldRequested(self::ORDER_SUBTOTALS_FIELD_NAME)) {
            return;
        }

        $config = $context->getConfig()->getField(self::ORDER_SUBTOTALS_FIELD_NAME)->getTargetEntity();
        if (null === $config) {
            return;
        }

        $data = $context->getData();
        $dataMap = $this->getDataMap($data, $context->getResultFieldName('id'));
        $allOrderSubtotals = $this->loadOrderSubtotals(
            array_keys($dataMap),
            $context->getSharedData()->get(self::ORDER_SUBTOTALS) ?? []
        );
        $normalizationContext = $context->getNormalizationContext();
        foreach ($allOrderSubtotals as $orderId => $orderSubtotals) {
            $dataKey = $dataMap[$orderId] ?? null;
            if (null !== $dataKey) {
                $data[$dataKey][self::ORDER_SUBTOTALS_FIELD_NAME] = $this->objectNormalizer->normalizeObjects(
                    $orderSubtotals,
                    $config,
                    $normalizationContext
                );
            }
        }
        $context->setData($data);
    }

    private function getDataMap(array $data, string $idFieldName): array
    {
        $dataMap = [];
        foreach ($data as $key => $item) {
            $dataMap[$item[$idFieldName]] = $key;
        }

        return $dataMap;
    }

    private function loadOrderSubtotals(array $orderIds, array $orderSubtotals): array
    {
        $orderIds = array_diff($orderIds, array_keys($orderSubtotals));
        if ($orderIds) {
            /** @var Order[] $orders */
            $orders = $this->doctrineHelper->createQueryBuilder(Order::class, 'o')
                ->select('o, li')
                ->innerJoin('o.lineItems', 'li')
                ->andWhere('o.id IN (:ids)')
                ->setParameter('ids', $orderIds)
                ->getQuery()
                ->setHint(Query::HINT_REFRESH, true)
                ->getResult();
            foreach ($orders as $order) {
                $orderSubtotals[$order->getId()] = $this->orderSubtotalRepository->getOrderSubtotals($order);
            }
        }

        return $orderSubtotals;
    }
}
