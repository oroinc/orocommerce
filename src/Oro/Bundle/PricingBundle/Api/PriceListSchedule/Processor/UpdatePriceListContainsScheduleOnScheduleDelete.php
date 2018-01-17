<?php

namespace Oro\Bundle\PricingBundle\Api\PriceListSchedule\Processor;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListSchedule;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class UpdatePriceListContainsScheduleOnScheduleDelete implements ProcessorInterface
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
        $schedule = $context->getResult();

        $this->deleteHandler->process($context);

        if (!$schedule instanceof PriceListSchedule) {
            return;
        }

        $schedule->getPriceList()->refreshContainSchedule();

        $this->doctrineHelper->getEntityManager(PriceList::class)->flush();
    }
}
