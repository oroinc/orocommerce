<?php

namespace Oro\Bundle\PricingBundle\Api\PriceListSchedule\Processor;

use Oro\Bundle\PricingBundle\Builder\CombinedPriceListActivationPlanBuilder;
use Oro\Bundle\PricingBundle\Entity\PriceListSchedule;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class BuildCombinedPriceListOnScheduleDeleteList implements ProcessorInterface
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
        $schedules = $this->getSchedulesFromContext($context);

        $this->deleteHandler->process($context);

        $processed = [];
        foreach ($schedules as $schedule) {
            if (!$schedule instanceof PriceListSchedule) {
                continue;
            }

            if (null === $schedule->getPriceList()) {
                continue;
            }

            $priceList = $schedule->getPriceList();

            if (in_array($priceList->getId(), $processed, true)) {
                continue;
            }

            $this->combinedPriceListBuilder->buildByPriceList($priceList);
            $processed[] = $priceList->getId();
        }
    }

    /**
     * @param ContextInterface $context
     *
     * @return array
     */
    private function getSchedulesFromContext(ContextInterface $context)
    {
        $schedules = $context->getResult();

        if (false === is_array($schedules)) {
            return [];
        }

        return $schedules;
    }
}
