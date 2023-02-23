<?php

namespace Oro\Bundle\PricingBundle\Api\Processor\ProductPrice;

use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Saves a price list ID to the context for later use.
 */
class StorePriceListInContext implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var SingleItemContext $context */

        /** @var PriceList|null $entity */
        $priceList = $context->getResult();
        if (null === $priceList || !$priceList->getId()) {
            return;
        }

        PriceListIdContextUtil::storePriceListId($context, $priceList->getId());
    }
}
