<?php

namespace Oro\Bundle\PricingBundle\Api\Processor\ProductPrice;

use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Saves a price list ID from product price to the context for later use.
 */
class StorePriceListInContextByProductPrice implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var SingleItemContext $context */

        /** @var ProductPrice|null $entity */
        $entity = $context->getResult();
        if (null === $entity) {
            return;
        }

        $priceList = $entity->getPriceList();
        if (null === $priceList || !$priceList->getId()) {
            return;
        }

        PriceListIdContextUtil::storePriceListId($context, $priceList->getId());
    }
}
