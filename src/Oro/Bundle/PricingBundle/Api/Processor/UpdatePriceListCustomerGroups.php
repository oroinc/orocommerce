<?php

namespace Oro\Bundle\PricingBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\SharedDataAwareContextInterface;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\PricingBundle\Model\PriceListRelationTriggerHandler;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Updates customer groups for price lists.
 */
class UpdatePriceListCustomerGroups implements ProcessorInterface
{
    /** data structure: [customer group id => [website id => [customer group, website], ...], ...] */
    private const CUSTOMER_GROUPS = 'price_list_customer_groups_to_update';

    private PriceListRelationTriggerHandler $relationChangesHandler;

    public function __construct(PriceListRelationTriggerHandler $relationChangesHandler)
    {
        $this->relationChangesHandler = $relationChangesHandler;
    }

    /**
     * Adds the given customer group and website to the list of customer groups
     * that require the price list relations update.
     * This list is stored in shared data.
     */
    public static function addCustomerGroupToUpdatePriceLists(
        SharedDataAwareContextInterface $context,
        CustomerGroup $customerGroup,
        Website $website
    ): void {
        $sharedData = $context->getSharedData();
        $customerGroups = $sharedData->get(self::CUSTOMER_GROUPS) ?? [];
        $customerGroups[$customerGroup->getId()][$website->getId()] = [$customerGroup, $website];
        $sharedData->set(self::CUSTOMER_GROUPS, $customerGroups);
    }

    /**
     * Moves customer groups that require the price list relations update from shared data to the given context.
     */
    public static function moveCustomerGroupsToUpdatePriceListsToContext(SharedDataAwareContextInterface $context): void
    {
        $sharedData = $context->getSharedData();
        if ($sharedData->has(self::CUSTOMER_GROUPS)) {
            $context->set(self::CUSTOMER_GROUPS, $sharedData->get(self::CUSTOMER_GROUPS));
            $sharedData->remove(self::CUSTOMER_GROUPS);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        $customerGroups = $context->get(self::CUSTOMER_GROUPS);
        foreach ($customerGroups as $items) {
            foreach ($items as [$customerGroup, $website]) {
                $this->relationChangesHandler->handleCustomerGroupChange($customerGroup, $website);
            }
        }
        $context->remove(self::CUSTOMER_GROUPS);
    }
}
