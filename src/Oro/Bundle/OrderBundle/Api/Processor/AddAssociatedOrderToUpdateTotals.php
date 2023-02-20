<?php

namespace Oro\Bundle\OrderBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds an order associated with an entity to the list of orders for which totals need to be updated.
 */
class AddAssociatedOrderToUpdateTotals implements ProcessorInterface
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
                $context->getData()->getOrder(),
                $context->getForm(),
                $context->findFormFieldName('order')
            );
        }
    }
}
