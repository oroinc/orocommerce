<?php

namespace Oro\Bundle\PricingBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Collects price lists for created, updated or deleted price rules to later update lexemes.
 */
class CollectPriceRulePriceListsToUpdateLexemes implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        $priceLists = $context->get(UpdatePriceListLexemes::PRICE_LISTS) ?? [];
        /** @var PriceRule[] $entities */
        $entities = $context->getAllEntities(true);
        foreach ($entities as $entity) {
            $priceList = $entity->getPriceList();
            if (null !== $priceList) {
                $priceLists[$priceList->getId()] = $priceList;
            }
        }
        if ($priceLists) {
            $context->set(UpdatePriceListLexemes::PRICE_LISTS, $priceLists);
        }
    }
}
