<?php

namespace Oro\Bundle\PricingBundle\Api\Processor;

use Oro\Bundle\PricingBundle\Builder\CombinedPriceListActivationPlanBuilder;
use Oro\Bundle\PricingBundle\Entity\PriceListSchedule;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class BuildCombinedPriceListOnScheduleDeleteListProcessor implements ProcessorInterface
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
        /** @var PriceListSchedule[] $schedules */
        $schedules = $context->getResult();
        if (!$schedules) {
            return;
        }

        $this->deleteHandler->process($context);

        $processed = [];
        foreach ($schedules as $schedule) {
            $priceList = $schedule->getPriceList();
            if (in_array($priceList->getId(), $processed, true)) {
                continue;
            }

            $this->combinedPriceListBuilder->buildByPriceList($priceList);
            $processed[] = $priceList->getId();
        }
    }
}
