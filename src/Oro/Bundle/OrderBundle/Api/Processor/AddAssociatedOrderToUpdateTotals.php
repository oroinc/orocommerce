<?php

namespace Oro\Bundle\OrderBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemPriceAwareInterface;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds an order associated with an entity to the list of orders for which totals need to be updated.
 */
class AddAssociatedOrderToUpdateTotals implements ProcessorInterface
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        if ($context->getForm()->isValid()) {
            $entity = $context->getData();
            $order = $entity instanceof ProductKitItemLineItemPriceAwareInterface
                ? $entity->getLineItem()->getOrder()
                : $entity->getOrder();

            UpdateOrderTotals::addOrderToUpdateTotals(
                $context,
                $order,
                $context->getForm(),
                $context->findFormFieldName('order')
            );
        }
    }
}
