<?php

namespace Oro\Bundle\PricingBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\ChangeContextInterface;
use Oro\Bundle\PricingBundle\Entity\PriceListSchedule;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Collects price lists for deleted schedules to later update these price lists.
 */
class CollectPriceListsToUpdatePriceListsContainSchedule implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var ChangeContextInterface $context */

        $entities = $context->getAllEntities(true);
        foreach ($entities as $entity) {
            if ($entity instanceof PriceListSchedule) {
                $priceList = $entity->getPriceList();
                if (null !== $priceList) {
                    UpdatePriceListsContainSchedule::addPriceListToUpdatePriceListsContainSchedule(
                        $context,
                        $priceList
                    );
                }
            }
        }
    }
}
