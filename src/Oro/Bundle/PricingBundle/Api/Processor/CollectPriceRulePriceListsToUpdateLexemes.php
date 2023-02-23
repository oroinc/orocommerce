<?php

namespace Oro\Bundle\PricingBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\ChangeContextInterface;
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
    public function process(ContextInterface $context): void
    {
        /** @var ChangeContextInterface $context */

        $entities = $context->getAllEntities(true);
        foreach ($entities as $entity) {
            if ($entity instanceof PriceRule) {
                $priceList = $entity->getPriceList();
                if (null !== $priceList) {
                    UpdatePriceListLexemes::addPriceListToUpdateLexemes($context, $priceList);
                }
            }
        }
    }
}
