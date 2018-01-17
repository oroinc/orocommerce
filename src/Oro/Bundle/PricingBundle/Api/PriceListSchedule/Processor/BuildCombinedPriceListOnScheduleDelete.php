<?php

namespace Oro\Bundle\PricingBundle\Api\PriceListSchedule\Processor;

use Oro\Bundle\PricingBundle\Builder\CombinedPriceListActivationPlanBuilder;
use Oro\Bundle\PricingBundle\Entity\PriceListSchedule;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class BuildCombinedPriceListOnScheduleDelete implements ProcessorInterface
{
    /**
     * @var CombinedPriceListActivationPlanBuilder
     */
    private $combinedPriceListBuilder;

    /**
     * @var ProcessorInterface
     */
    private $deleteHandler;

    /**
     * @param CombinedPriceListActivationPlanBuilder $combinedPriceListBuilder
     * @param ProcessorInterface                     $deleteHandler
     */
    public function __construct(
        CombinedPriceListActivationPlanBuilder $combinedPriceListBuilder,
        ProcessorInterface $deleteHandler
    ) {
        $this->combinedPriceListBuilder = $combinedPriceListBuilder;
        $this->deleteHandler = $deleteHandler;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context)
    {
        $schedule = $context->getResult();

        $this->deleteHandler->process($context);

        if (!$schedule instanceof PriceListSchedule) {
            return;
        }

        $this->combinedPriceListBuilder->buildByPriceList($schedule->getPriceList());
    }
}
