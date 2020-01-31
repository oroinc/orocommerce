<?php

namespace Oro\Bundle\OrderBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Total\TotalHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ParameterBagInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Calculates totals, subtotals and discounts for orders and their line items.
 * It is expected that MoveSharedDataToContext processor is executed before this processor.
 * @see \Oro\Bundle\ApiBundle\Processor\Shared\MoveSharedDataToContext
 */
class UpdateOrderTotals implements ProcessorInterface
{
    private const ORDERS = 'orders_to_update_totals';

    /** @var TotalHelper */
    private $totalHelper;

    /**
     * @param TotalHelper $totalHelper
     */
    public function __construct(TotalHelper $totalHelper)
    {
        $this->totalHelper = $totalHelper;
    }

    /**
     * @param Order                 $order
     * @param ParameterBagInterface $sharedData
     */
    public static function addOrderToUpdateTotals(Order $order, ParameterBagInterface $sharedData): void
    {
        $orderKey = $order->getId();
        if (null === $orderKey) {
            $orderKey = spl_object_hash($order);
        }

        $orders = $sharedData->get(self::ORDERS) ?? [];
        $orders[$orderKey] = $order;
        $sharedData->set(self::ORDERS, $orders);
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var CustomizeFormDataContext $context */

        if (!$this->isMasterRequest($context)) {
            return;
        }

        $orders = $context->get(self::ORDERS);
        foreach ($orders as $order) {
            $this->totalHelper->fill($order);
        }
        $context->remove(self::ORDERS);
    }

    /**
     * @param CustomizeFormDataContext $context
     *
     * @return bool
     */
    private function isMasterRequest(CustomizeFormDataContext $context): bool
    {
        $includedEntities = $context->getIncludedEntities();

        return
            null === $includedEntities
            || $includedEntities->getPrimaryEntity() === $context->getData();
    }
}
