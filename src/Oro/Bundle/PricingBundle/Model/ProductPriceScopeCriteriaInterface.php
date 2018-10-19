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
     * @return object|null
     */
    public function getContext();

    /**
     * @return Website
     */
    public function getWebsite();

    /**
     * @param string $key
     * @return mixed
     */
    public function getData($key);

    /**
     * @param string $key
     * @param mixed $value
     */
    public function setData($key, $value);

    /**
     * @param string $key
     */
    public function unsetData($key);
}
