<?php

namespace Oro\Bundle\PricingBundle\Api\PriceListSchedule\Processor;

use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListSchedule;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Updates "containSchedule" field for price lists related to price list schedules
 * stored in "price_list_schedules" attribute of the context.
 */
class UpdatePriceListsContainSchedule implements ProcessorInterface
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var PriceListSchedule[] $schedules */
        $schedules = $context->get(SavePriceListSchedulesToContext::PRICE_LIST_SCHEDULES);
        if ($schedules) {
            foreach ($schedules as $schedule) {
                $priceList = $schedule->getPriceList();
                if (null !== $priceList) {
                    $priceList->refreshContainSchedule();
                }
            }
            $this->doctrineHelper->getEntityManager(PriceList::class)->flush();
        }
    }
}
