<?php

namespace Oro\Bundle\PricingBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\ChangeContextInterface;
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
    public function process(ContextInterface $context): void
    {
        /** @var ChangeContextInterface $context */

        $entities = $context->getAllEntities(true);
        foreach ($entities as $entity) {
            if ($entity instanceof PriceList) {
                UpdatePriceListLexemes::addPriceListToUpdateLexemes($context, $entity);
            }
        }
    }
}
