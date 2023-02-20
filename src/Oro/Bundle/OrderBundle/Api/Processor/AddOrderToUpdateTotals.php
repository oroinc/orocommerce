<?php

namespace Oro\Bundle\OrderBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds an order to the list of orders for which totals need to be updated.
 */
class AddOrderToUpdateTotals implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        if ($context->getForm()->isValid()) {
            UpdateOrderTotals::addOrderToUpdateTotals(
                $context,
                $context->getData(),
                $context->getForm()
            );
        }
    }
}
