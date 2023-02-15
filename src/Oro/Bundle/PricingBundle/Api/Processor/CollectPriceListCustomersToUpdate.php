<?php

namespace Oro\Bundle\PricingBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\ChangeContextInterface;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerFallback;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomer;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Collects customers for created, updated or deleted price list to customer relations to later update.
 */
class CollectPriceListCustomersToUpdate implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var ChangeContextInterface $context */

        $entities = $context->getAllEntities(true);
        foreach ($entities as $entity) {
            if ($entity instanceof PriceListToCustomer || $entity instanceof PriceListCustomerFallback) {
                $customer = $entity->getCustomer();
                $website = $entity->getWebsite();
                if (null !== $customer && null !== $website) {
                    UpdatePriceListCustomers::addCustomerToUpdatePriceLists($context, $customer, $website);
                }
            }
        }
    }
}
