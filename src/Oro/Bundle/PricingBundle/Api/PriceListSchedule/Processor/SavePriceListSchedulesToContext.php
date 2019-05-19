<?php

namespace Oro\Bundle\PricingBundle\Api\PriceListSchedule\Processor;

use Oro\Bundle\ApiBundle\Processor\ListContext;
use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\PricingBundle\Entity\PriceListSchedule;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Copies price list schedules from "result" attribute of the context
 * to "price_list_schedules" attribute of the context.
 */
class SavePriceListSchedulesToContext implements ProcessorInterface
{
    public const PRICE_LIST_SCHEDULES = 'price_list_schedules';

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context)
    {
        $schedules = [];
        $data = $context->getResult();
        if ($context instanceof SingleItemContext) {
            // executed for "delete" action
            if ($data instanceof PriceListSchedule) {
                $schedules[] = $data;
            }
        } elseif ($context instanceof ListContext && is_array($data)) {
            // executed for "delete_list" action
            foreach ($data as $item) {
                if ($item instanceof PriceListSchedule) {
                    $schedules[] = $item;
                }
            }
        }

        if ($schedules) {
            $context->set(self::PRICE_LIST_SCHEDULES, $schedules);
        }
    }
}
