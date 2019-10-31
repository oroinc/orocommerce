<?php

namespace Oro\Bundle\PricingBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerGroupFallback;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomerGroup;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Collects customer groups for created, updated or deleted price list to customer group relations to later update.
 */
class CollectPriceListCustomerGroupsToUpdate implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        $customerGroups = $context->get(UpdatePriceListCustomerGroups::CUSTOMER_GROUPS) ?? [];
        /** @var PriceListToCustomerGroup[]|PriceListCustomerGroupFallback[] $entities */
        $entities = $context->getAllEntities(true);
        foreach ($entities as $entity) {
            $customerGroup = $entity->getCustomerGroup();
            $website = $entity->getWebsite();
            if (null !== $customerGroup && null !== $website) {
                $customerGroups[$customerGroup->getId()][$website->getId()] = [$customerGroup, $website];
            }
        }
        if ($customerGroups) {
            $context->set(UpdatePriceListCustomerGroups::CUSTOMER_GROUPS, $customerGroups);
        }
    }
}
