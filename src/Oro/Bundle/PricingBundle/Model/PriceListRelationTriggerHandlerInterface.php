<?php

namespace Oro\Bundle\PricingBundle\Model;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * Provides a set of methods to handle price list collection changes.
 */
interface PriceListRelationTriggerHandlerInterface
{
    /**
     * Handle relation changes on config level.
     */
    public function handleConfigChange(): void;

    /**
     * Initiate full rebuild.
     */
    public function handleFullRebuild(): void;

    /**
     * Handle relation changes on website level.
     *
     * @param Website $website
     */
    public function handleWebsiteChange(Website $website): void;

    /**
     * Handle relation changes on customer group level.
     *
     * @param CustomerGroup $customerGroup
     * @param Website $website
     */
    public function handleCustomerGroupChange(CustomerGroup $customerGroup, Website $website): void;

    /**
     * Handle relations on customer group remove.
     *
     * @param CustomerGroup $customerGroup
     */
    public function handleCustomerGroupRemove(CustomerGroup $customerGroup): void;

    /**
     * Handle relation changes on customer level.
     *
     * @param Customer $customer
     * @param Website $website
     */
    public function handleCustomerChange(Customer $customer, Website $website): void;

    /**
     * Handle price list status changes.
     *
     * @param PriceList $priceList
     */
    public function handlePriceListStatusChange(PriceList $priceList): void;
}
