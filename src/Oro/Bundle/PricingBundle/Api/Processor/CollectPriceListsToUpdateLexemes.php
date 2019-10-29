<?php

namespace Oro\Bundle\PricingBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Collects created or updated price lists to later update lexemes.
 */
class CollectPriceListsToUpdateLexemes implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        $priceLists = $context->get(UpdatePriceListLexemes::PRICE_LISTS) ?? [];
        $entities = $context->getAllEntities();
        foreach ($entities as $entity) {
            if ($entity instanceof PriceList) {
                $priceLists[$entity->getId()] = $entity;
            }
        }
        if ($priceLists) {
            $context->set(UpdatePriceListLexemes::PRICE_LISTS, $priceLists);
        }
    }
}
