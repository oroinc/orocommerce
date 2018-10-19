<?php

namespace Oro\Bundle\PricingBundle\Model;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\WebsiteBundle\Entity\Website;

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
