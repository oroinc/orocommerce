<?php

namespace Oro\Bundle\PricingBundle\Api\Processor\ProductPrice;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Gets a value of the "priceList" filter and saves the price list ID to context.
 */
class HandlePriceListFilter implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        $priceListFilterValue = $context->getFilterValues()->get('priceList');
        if (null !== $priceListFilterValue) {
            PriceListIdContextUtil::storePriceListId($context, $priceListFilterValue->getValue());
        } else {
            $context->addError(
                Error::createValidationError(Constraint::FILTER, 'The "priceList" filter is required.')
            );
        }
    }
}
