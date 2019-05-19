<?php

namespace Oro\Bundle\PricingBundle\Api\PriceListSchedule\Processor;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Removes "price_list_schedules" attribute from the context.
 */
class RemovePriceListSchedulesFromContext implements ProcessorInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context)
    {
        if ($context->has(SavePriceListSchedulesToContext::PRICE_LIST_SCHEDULES)) {
            $context->remove(SavePriceListSchedulesToContext::PRICE_LIST_SCHEDULES);
        }
    }
}
