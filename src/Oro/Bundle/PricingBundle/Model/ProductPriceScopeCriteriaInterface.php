<?php

namespace Oro\Bundle\PricingBundle\Model;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\WebsiteBundle\Entity\Website;

interface ProductPriceScopeCriteriaInterface
{
    /**
     * @return Customer
     */
    public function getCustomer();

    /**
     * @return CustomerGroup
     */
    public function getCustomerGroup();

    /**
     * @return Website
     */
    public function getWebsite();
}
