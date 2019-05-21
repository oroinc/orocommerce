<?php

namespace Oro\Bundle\PricingBundle\Api\PriceListSchedule\Processor;

use Oro\Bundle\PricingBundle\Builder\CombinedPriceListActivationPlanBuilder;
use Oro\Bundle\PricingBundle\Entity\PriceListSchedule;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Builds combined price lists for price list schedules
 * stored in "price_list_schedules" attribute of the context.
 */
class BuildCombinedPriceLists implements ProcessorInterface
{
    /** @var CombinedPriceListActivationPlanBuilder */
    private $combinedPriceListBuilder;

    /**
     * @param CombinedPriceListActivationPlanBuilder $combinedPriceListBuilder
     */
    public function __construct(CombinedPriceListActivationPlanBuilder $combinedPriceListBuilder)
    {
        $this->combinedPriceListBuilder = $combinedPriceListBuilder;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var PriceListSchedule[] $schedules */
        $schedules = $context->get(SavePriceListSchedulesToContext::PRICE_LIST_SCHEDULES);
        if ($schedules) {
            $processed = [];
            foreach ($schedules as $schedule) {
                $priceList = $schedule->getPriceList();
                if (null !== $priceList && !in_array($priceList->getId(), $processed, true)) {
                    $this->combinedPriceListBuilder->buildByPriceList($priceList);
                    $processed[] = $priceList->getId();
                }
            }
        }
    }
}
