<?php

namespace Oro\Bundle\PricingBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Batch\Processor\Update\BatchUpdateContext;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Collects created or updated price lists to later update lexemes in Batch API.
 */
class BatchCollectPriceListsToUpdateLexemes implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var BatchUpdateContext $context */

        $priceLists = $context->get(UpdatePriceListLexemes::PRICE_LISTS) ?? [];
        $items = $context->getBatchItemsProcessedWithoutErrors();
        foreach ($items as $item) {
            $itemTargetContext = $item->getContext()->getTargetContext();
            if (null === $itemTargetContext) {
                continue;
            }
            $entities = $itemTargetContext->getAllEntities();
            foreach ($entities as $entity) {
                if ($entity instanceof PriceList) {
                    $priceLists[$entity->getId()] = $entity;
                }
            }
        }
        if ($priceLists) {
            $context->set(UpdatePriceListLexemes::PRICE_LISTS, $priceLists);
        }
    }
}
