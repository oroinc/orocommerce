<?php

namespace Oro\Bundle\OrderBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Moves orders that require the totals update from shared data to the current context.
 * It is done to avoid loading of the {@see \Oro\Bundle\OrderBundle\Api\Processor\UpdateOrderTotals}
 * processor and services are used by it when there are no orders that require the totals update.
 */
class MoveOrdersRequireTotalsUpdateToContext implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        if ($context->isPrimaryEntityRequest()) {
            UpdateOrderTotals::moveOrdersRequireTotalsUpdateToContext($context);
        }
    }
}
