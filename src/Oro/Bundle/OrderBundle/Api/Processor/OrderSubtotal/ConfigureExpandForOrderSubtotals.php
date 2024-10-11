<?php

namespace Oro\Bundle\OrderBundle\Api\Processor\OrderSubtotal;

use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;
use Oro\Bundle\OrderBundle\Api\Model\OrderSubtotal;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Expands order subtotals relationship for an order.
 */
class ConfigureExpandForOrderSubtotals implements ProcessorInterface
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var ConfigContext $context */

        $orderSubtotalsField = $context->getResult()->getField(OrderSubtotal::API_RELATION_KEY);
        if (!$orderSubtotalsField) {
            return;
        }

        $orderSubtotalsField->setCollapsed(false);
    }
}
