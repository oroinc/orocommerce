<?php

namespace Oro\Bundle\OrderBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Moves orders to update totals from shared data to the current context.
 */
class MoveOrderToUpdateTotalsFromSharedDataToContext implements ProcessorInterface
{
    private const ORDERS = 'orders_to_update_totals';

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var CustomizeFormDataContext $context */

        $sharedData = $context->getSharedData();
        if ($sharedData->has(self::ORDERS)) {
            $context->set(self::ORDERS, $sharedData->get(self::ORDERS));
            $sharedData->remove(self::ORDERS);
        }
    }
}
