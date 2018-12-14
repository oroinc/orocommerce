<?php

namespace Oro\Bundle\PricingBundle\Model;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * Declares methods to create instance of ProductPriceScopeCriteriaInterface
 * by either direct self::create() call or from a given context
 */
interface ProductPriceScopeCriteriaFactoryInterface
{
    /**
     * @param Website|null $website
     * @param Customer|null $customer
     * @param null $context
     * @param array $data
     * @return ProductPriceScopeCriteriaInterface
     */
    public function create(
        Website $website = null,
        Customer $customer = null,
        $context = null,
        array $data = []
    ): ProductPriceScopeCriteriaInterface;

    /**
     * @param object $context
     * @param array $data
     * @return ProductPriceScopeCriteriaInterface
     */
    public function createByContext($context, array $data = []): ProductPriceScopeCriteriaInterface;
}
