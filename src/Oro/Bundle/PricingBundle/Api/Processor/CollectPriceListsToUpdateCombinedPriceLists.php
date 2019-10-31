<?php

namespace Oro\Bundle\PricingBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\PricingBundle\Entity\PriceListSchedule;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Collects price lists with added, updated or deleted schedules to later update lexemes.
 */
class CollectPriceListsToUpdateCombinedPriceLists implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        $priceLists = $context->get(UpdateCombinedPriceLists::PRICE_LISTS) ?? [];
        $entities = $context->getAllEntities();
        foreach ($entities as $entity) {
            if ($entity instanceof PriceListSchedule) {
                $priceList = $entity->getPriceList();
                if (null !== $priceList) {
                    $priceLists[$priceList->getId()] = $priceList;
                }
            }
        }
        if ($priceLists) {
            $context->set(UpdateCombinedPriceLists::PRICE_LISTS, $priceLists);
        }
    }
}
