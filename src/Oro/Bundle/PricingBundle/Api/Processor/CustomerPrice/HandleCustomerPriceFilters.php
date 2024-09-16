<?php

namespace Oro\Bundle\PricingBundle\Api\Processor\CustomerPrice;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Checks on required filters.
 */
class HandleCustomerPriceFilters implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     * @param ContextInterface|Context $context
     */
    public function process(ContextInterface $context): void
    {
        $filterValues = $context->getFilterValues();
        if (!$filterValues->getOne('customer')) {
            $context->addError(
                Error::createValidationError(Constraint::FILTER, 'The "customer" filter is required.')
            );
        }

        if (!$filterValues->getOne('website')) {
            $context->addError(
                Error::createValidationError(Constraint::FILTER, 'The "website" filter is required.')
            );
        }

        if (!$filterValues->getOne('product')) {
            $context->addError(
                Error::createValidationError(Constraint::FILTER, 'The "product" filter is required.')
            );
        }
    }
}
