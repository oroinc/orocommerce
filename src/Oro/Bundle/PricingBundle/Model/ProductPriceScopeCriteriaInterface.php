<?php

namespace Oro\Bundle\PricingBundle\Model;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\WebsiteBundle\Entity\Website;

interface ProductPriceScopeCriteriaInterface
{
    /**
     * @param Customer|null $customer
     */
    public function setCustomer(Customer $customer = null);

    /**
     * @return Customer|null
     */
    public function getCustomer();

    /**
     * @param object $context
     */
    public function setContext($context);

    /**
     * @return object|null
     */
    public function getContext();

    /**
     * @param Website|null $website
     */
    public function setWebsite(Website $website = null);

    /**
     * @return Website|null
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
