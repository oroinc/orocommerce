<?php

namespace Oro\Bundle\PricingBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Batch\Processor\Update\BatchUpdateContext;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Collects price lists for created or updated price rules to later update lexemes in Batch API.
 */
class BatchCollectPriceRulePriceListsToUpdateLexemes implements ProcessorInterface
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
            if (!is_a($item->getContext()->getClassName(), PriceRule::class, true)) {
                continue;
            }
            $itemTargetContext = $item->getContext()->getTargetContext();
            if (null === $itemTargetContext) {
                continue;
            }
            /** @var PriceRule[] $entities */
            $entities = $itemTargetContext->getAllEntities(true);
            foreach ($entities as $entity) {
                $priceList = $entity->getPriceList();
                if (null !== $priceList) {
                    $priceLists[$priceList->getId()] = $priceList;
                }
            }
        }
        if ($priceLists) {
            $context->set(UpdatePriceListLexemes::PRICE_LISTS, $priceLists);
        }
    }
}
