<?php

namespace Oro\Bundle\PricingBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\ChangeContextInterface;
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
    public function process(ContextInterface $context): void
    {
        /** @var ChangeContextInterface $context */

        $entities = $context->getAllEntities(true);
        foreach ($entities as $entity) {
            if ($entity instanceof PriceListToCustomerGroup || $entity instanceof PriceListCustomerGroupFallback) {
                $customerGroup = $entity->getCustomerGroup();
                $website = $entity->getWebsite();
                if (null !== $customerGroup && null !== $website) {
                    UpdatePriceListCustomerGroups::addCustomerGroupToUpdatePriceLists(
                        $context,
                        $customerGroup,
                        $website
                    );
                }
            }
        }
    }
}
