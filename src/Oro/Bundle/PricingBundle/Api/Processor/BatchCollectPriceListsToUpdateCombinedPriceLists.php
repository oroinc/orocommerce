<?php

namespace Oro\Bundle\PricingBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Batch\Processor\Update\BatchUpdateContext;
use Oro\Bundle\PricingBundle\Entity\PriceListSchedule;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Collects price lists with added or updated schedules to later update lexemes in Batch API.
 */
class BatchCollectPriceListsToUpdateCombinedPriceLists implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var BatchUpdateContext $context */

        $priceLists = $context->get(UpdateCombinedPriceLists::PRICE_LISTS) ?? [];
        $items = $context->getBatchItemsProcessedWithoutErrors();
        foreach ($items as $item) {
            $itemTargetContext = $item->getContext()->getTargetContext();
            if (null === $itemTargetContext) {
                continue;
            }
            $entities = $itemTargetContext->getAllEntities();
            foreach ($entities as $entity) {
                if ($entity instanceof PriceListSchedule) {
                    $priceList = $entity->getPriceList();
                    if (null !== $priceList) {
                        $priceLists[$priceList->getId()] = $priceList;
                    }
                }
            }
        }
        if ($priceLists) {
            $context->set(UpdateCombinedPriceLists::PRICE_LISTS, $priceLists);
        }
    }
}
