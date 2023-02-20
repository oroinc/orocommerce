<?php

namespace Oro\Bundle\PricingBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\ChangeContextInterface;
use Oro\Bundle\PricingBundle\Entity\PriceListSchedule;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Collects price lists with added, updated or deleted schedules to later build combined price lists.
 */
class CollectPriceListsToUpdateCombinedPriceLists implements ProcessorInterface
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
                    UpdateCombinedPriceLists::addPriceListToUpdate($context, $priceList);
                }
            }
        }
    }
}
