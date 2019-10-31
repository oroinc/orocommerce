<?php

namespace Oro\Bundle\PricingBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Context;
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
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        $customers = $context->get(UpdatePriceListCustomers::CUSTOMERS) ?? [];
        /** @var PriceListToCustomer[]|PriceListCustomerFallback[] $entities */
        $entities = $context->getAllEntities(true);
        foreach ($entities as $entity) {
            $customer = $entity->getCustomer();
            $website = $entity->getWebsite();
            if (null !== $customer && null !== $website) {
                $customers[$customer->getId()][$website->getId()] = [$customer, $website];
            }
        }
        if ($customers) {
            $context->set(UpdatePriceListCustomers::CUSTOMERS, $customers);
        }
    }
}
