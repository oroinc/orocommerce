<?php

namespace Oro\Bundle\ProductBundle\Api\Processor\ProductLatestPurchase;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\ListContext;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Checks that all required filters are provided.
 */
class HandleProductLatestPurchasesFilters implements ProcessorInterface
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var ListContext $context */

        $filterValues = $context->getFilterValues();
        $hasCustomerFilter = $filterValues->getOne('customer') !== null;
        $hasHierarchicalCustomerFilter = $filterValues->getOne('hierarchicalCustomer') !== null;
        $hasCustomerUserFilter = $filterValues->getOne('customerUser') !== null;

        if (!$hasCustomerFilter && !$hasHierarchicalCustomerFilter && !$hasCustomerUserFilter) {
            $context->addError(Error::createValidationError(
                Constraint::FILTER,
                'Either the "customer" or "hierarchicalCustomer" or "customerUser" filter must be provided.'
            ));
        }
        if ($hasCustomerFilter && $hasHierarchicalCustomerFilter) {
            $context->addError(Error::createValidationError(
                Constraint::FILTER,
                'The "customer" and "hierarchicalCustomer" filters cannot be used together.'
            ));
        }

        if (!$filterValues->getOne('product')) {
            $context->addError(Error::createValidationError(
                Constraint::FILTER,
                'The "product" filter is required.'
            ));
        }
    }
}
