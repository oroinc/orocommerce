<?php

namespace Oro\Bundle\OrderBundle\Api\Processor\OrderSubtotal;

use Doctrine\ORM\Query;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\OrderBundle\Api\Model\OrderSubtotal;
use Oro\Bundle\OrderBundle\Api\Repository\OrderSubtotalRepository;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Add order subtotals for orders.
 */
class AddOrderSubtotalsToOrder implements ProcessorInterface
{
    public function __construct(
        private OrderSubtotalRepository $orderSubtotalRepository,
        private DoctrineHelper $doctrineHelper
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext|CustomizeFormDataContext $context */

        if ($context->getAction() === 'customize_loaded_data') {
            if ($context->getSharedData()->has(OrderSubtotal::API_RELATION_KEY)) {
                $this->handleSharedData($context);
                return;
            }

            $this->handleGetActions($context);
        } elseif ($context->getAction() === 'customize_form_data') {
            $this->handleRollbackValidateOperation($context);
        }
    }

    private function handleGetActions(CustomizeLoadedDataContext $context): void
    {
        $data = $this->prepareData($context);
        $orders = $this->getOrders(\array_keys($data));

        $config = $context->getConfig()->getField(OrderSubtotal::API_RELATION_KEY)?->getTargetEntity();
        $normalizationContext = $context->getNormalizationContext();

        foreach ($orders as $order) {
            $orderSubtotals = $this->orderSubtotalRepository->getNormalizedOrderSubtotals(
                $order,
                $config,
                $normalizationContext
            );

            $data[$order->getId()][OrderSubtotal::API_RELATION_KEY] = $orderSubtotals;
        }

        $context->setData($data);
    }

    private function handleRollbackValidateOperation(CustomizeFormDataContext $context): void
    {
        $order = $context->getData();
        $orderSubtotals = $this->orderSubtotalRepository->getNormalizedOrderSubtotals(
            $order,
            $context->getConfig()->getField(OrderSubtotal::API_RELATION_KEY)->getTargetEntity(),
            $context->getNormalizationContext()
        );

        $context->getSharedData()->set(OrderSubtotal::API_RELATION_KEY, [$order->getId() => $orderSubtotals]);
    }

    private function handleSharedData(CustomizeLoadedDataContext $context): void
    {
        $data = $context->getData();

        $orderSubtotalsData = $context->getSharedData()->get(OrderSubtotal::API_RELATION_KEY);
        foreach ($data as &$datum) {
            if (isset($orderSubtotalsData[$datum['id']])) {
                $datum[OrderSubtotal::API_RELATION_KEY] = $orderSubtotalsData[$datum['id']];
            }
        }

        $context->setData($data);
    }

    private function getOrders(array $ids): array
    {
        $qb = $this->doctrineHelper->createQueryBuilder(Order::class, 'o');

        return $qb
            ->andWhere($qb->expr()->in('o.id', ':ids'))
            ->setParameter('ids', $ids)
            ->getQuery()
            ->setHint(Query::HINT_REFRESH, true)
            ->getResult();
    }

    private function prepareData(CustomizeLoadedDataContext $context): array
    {
        $data = [];
        foreach ($context->getData() as $datum) {
            $data[$datum['id']] = $datum;
        }

        return $data;
    }
}
