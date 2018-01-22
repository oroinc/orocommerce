<?php

namespace Oro\Bundle\PricingBundle\Api\PriceListSchedule\Processor;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListSchedule;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class UpdatePriceListContainsScheduleOnScheduleDeleteList implements ProcessorInterface
{
    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @var ProcessorInterface
     */
    private $deleteHandler;

    /**
     * @param DoctrineHelper     $doctrineHelper
     * @param ProcessorInterface $deleteHandler
     */
    public function __construct(DoctrineHelper $doctrineHelper, ProcessorInterface $deleteHandler)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->deleteHandler = $deleteHandler;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context)
    {
        $schedules = $this->getSchedulesFromContext($context);

        $this->deleteHandler->process($context);

        if ([] === $schedules) {
            return;
        }

        foreach ($schedules as $schedule) {
            if (!$schedule instanceof PriceListSchedule) {
                continue;
            }

            if (null === $schedule->getPriceList()) {
                continue;
            }

            $schedule->getPriceList()->refreshContainSchedule();
        }

        $this->doctrineHelper->getEntityManager(PriceList::class)->flush();
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
