<?php

namespace Oro\Bundle\OrderBundle\Api\Processor\OrderSubtotal;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\ListContext;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Checks that all required filters are provided.
 */
class HandleOrderSubtotalFilters implements ProcessorInterface
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var ListContext $context */

        $filterValues = $context->getFilterValues();
        if (!$filterValues->getOne('order')) {
            $context->addError(
                Error::createValidationError(Constraint::FILTER, 'The "order" filter is required.')
            );
        }
    }
}
