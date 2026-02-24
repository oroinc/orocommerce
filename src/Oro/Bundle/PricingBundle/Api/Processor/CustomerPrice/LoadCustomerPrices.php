<?php

namespace Oro\Bundle\PricingBundle\Api\Processor\CustomerPrice;

use Oro\Bundle\ApiBundle\Processor\ListContext;
use Oro\Bundle\PricingBundle\Api\Repository\CustomerPriceRepository;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Loads customer prices.
 */
class LoadCustomerPrices implements ProcessorInterface
{
    public function __construct(
        private CustomerPriceRepository $customerPricesRepository
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
            $this->customerPricesRepository->getCustomerPrices($context->getCriteria(), $context->getRequestType())
        );
    }
}
