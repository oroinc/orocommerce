<?php

namespace Oro\Bundle\PricingBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\SharedDataAwareContextInterface;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\PricingBundle\Model\PriceListRelationTriggerHandler;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Updates customers for price lists.
 */
class UpdatePriceListCustomers implements ProcessorInterface
{
    /** data structure: [customer id => [website id => [customer, website], ...], ...] */
    private const CUSTOMERS = 'price_list_customers_to_update';

    private PriceListRelationTriggerHandler $relationChangesHandler;

    public function __construct(PriceListRelationTriggerHandler $relationChangesHandler)
    {
        $this->relationChangesHandler = $relationChangesHandler;
    }

    /**
     * Adds the given customer and website to the list of customers that require the price list relations update.
     * This list is stored in shared data.
     */
    public static function addCustomerToUpdatePriceLists(
        SharedDataAwareContextInterface $context,
        Customer $customer,
        Website $website
    ): void {
        $sharedData = $context->getSharedData();
        $customers = $sharedData->get(self::CUSTOMERS) ?? [];
        $customers[$customer->getId()][$website->getId()] = [$customer, $website];
        $sharedData->set(self::CUSTOMERS, $customers);
    }

    /**
     * Moves customers that require the price list relations update from shared data to the given context.
     */
    public static function moveCustomersToUpdatePriceListsToContext(SharedDataAwareContextInterface $context): void
    {
        $sharedData = $context->getSharedData();
        if ($sharedData->has(self::CUSTOMERS)) {
            $context->set(self::CUSTOMERS, $sharedData->get(self::CUSTOMERS));
            $sharedData->remove(self::CUSTOMERS);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        $customers = $context->get(self::CUSTOMERS);
        foreach ($customers as $items) {
            foreach ($items as [$customer, $website]) {
                $this->relationChangesHandler->handleCustomerChange($customer, $website);
            }
        }
        $context->remove(self::CUSTOMERS);
    }
}
