<?php

namespace Oro\Bundle\PricingBundle\Api\Processor;

use Oro\Bundle\PricingBundle\Builder\CombinedPriceListActivationPlanBuilder;
use Oro\Bundle\PricingBundle\Entity\PriceListSchedule;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class BuildCombinedPriceListOnScheduleSaveProcessor implements ProcessorInterface
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
        /** @var PriceListSchedule $schedule */
        $schedule = $context->getResult();
        if (!$schedule) {
            return;
        }

        $this->combinedPriceListBuilder->buildByPriceList($schedule->getPriceList());
    }
}
