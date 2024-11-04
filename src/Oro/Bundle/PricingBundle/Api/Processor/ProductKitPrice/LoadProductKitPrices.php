<?php

namespace Oro\Bundle\PricingBundle\Api\Processor\ProductKitPrice;

use Oro\Bundle\ApiBundle\Processor\ListContext;
use Oro\Bundle\PricingBundle\Api\Repository\ProductKitPriceRepository;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Loads product kit prices.
 */
class LoadProductKitPrices implements ProcessorInterface
{
    public function __construct(
        private ProductKitPriceRepository $productKitPriceRepository
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var ListContext $context */

        if ($context->hasResult()) {
            // data already retrieved
            return;
        }

        $context->setResult(
            $this->productKitPriceRepository->getProductKitPrices(
                $context->getCriteria(),
                $context->getFilterValues()->getAll()
            )
        );
    }
}
