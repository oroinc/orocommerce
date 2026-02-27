<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Api\Processor\ProductPrice;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Saves a price list IDs of submitted product prices to the context for later use.
 */
class AddToPriceListIdMapInContext implements ProcessorInterface
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        /** @var ProductPrice|null $productPrice */
        $productPrice = $context->getForm()->getData();
        if (null === $productPrice) {
            return;
        }

        $productPriceId = $productPrice->getId();
        if (null !== $productPriceId) {
            $priceListId = $productPrice->getPriceList()?->getId();
            if (null !== $priceListId) {
                PriceListIdContextUtil::addToPriceListIdMap($context, $productPriceId, $priceListId);
            }
        }
    }
}
