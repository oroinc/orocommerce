<?php

namespace Oro\Bundle\PricingBundle\Api\PriceListSchedule\Processor;

use Oro\Bundle\PricingBundle\Builder\CombinedPriceListActivationPlanBuilder;
use Oro\Bundle\PricingBundle\Entity\PriceListSchedule;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class BuildCombinedPriceListOnScheduleSave implements ProcessorInterface
{
    /**
     * @var CombinedPriceListActivationPlanBuilder
     */
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
        $schedule = $context->getResult();
        if (!$schedule instanceof PriceListSchedule) {
            return;
        }

        $this->combinedPriceListBuilder->buildByPriceList($schedule->getPriceList());
    }
}
