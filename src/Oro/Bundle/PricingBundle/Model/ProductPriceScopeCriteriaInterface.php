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
    public function getCustomer(): ?Customer;

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
    public function getWebsite(): ?Website;

    /**
     * @param string $key
     * @return mixed
     */
    public function getData(string $key);

    /**
     * @param string $key
     * @param mixed $value
     */
    public function setData(string $key, $value);

    /**
     * @param string $key
     */
    public function unsetData(string $key);
}
