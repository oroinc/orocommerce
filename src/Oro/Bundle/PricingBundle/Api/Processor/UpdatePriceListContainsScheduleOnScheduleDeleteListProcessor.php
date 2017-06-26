<?php

namespace Oro\Bundle\PricingBundle\Api\Processor;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListSchedule;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class UpdatePriceListContainsScheduleOnScheduleDeleteListProcessor implements ProcessorInterface
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
        /** @var PriceListSchedule[] $schedules */
        $schedules = $context->getResult();
        if (!$schedules) {
            return;
        }

        $this->deleteHandler->process($context);

        foreach ($schedules as $schedule) {
            $schedule->getPriceList()->refreshContainSchedule();
        }

        $this->doctrineHelper->getEntityManager(PriceList::class)->flush();
    }
}
