<?php

namespace Oro\Bundle\PricingBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Context;
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
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        $priceLists = $context->get(UpdatePriceListsContainSchedule::PRICE_LISTS) ?? [];
        /** @var PriceListSchedule[] $entities */
        $entities = $context->getAllEntities(true);
        foreach ($entities as $entity) {
            $priceList = $entity->getPriceList();
            if (null !== $priceList) {
                $priceLists[$priceList->getId()] = $priceList;
            }
        }
        if ($priceLists) {
            $context->set(UpdatePriceListsContainSchedule::PRICE_LISTS, $priceLists);
        }
    }
}
