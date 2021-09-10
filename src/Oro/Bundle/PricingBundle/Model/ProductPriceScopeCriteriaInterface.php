<?php

namespace Oro\Bundle\PricingBundle\Model;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * Declares set of methods which allow to interact with information which could be used
 * to affect on product price search results
 */
interface ProductPriceScopeCriteriaInterface
{
    public function setCustomer(Customer $customer = null);

    public function getCustomer(): ?Customer;

    /**
     * @param object $context
     */
    public function setContext($context);

    /**
     * @return object|null
     */
    public function getContext();

    public function setWebsite(Website $website = null);

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

    public function unsetData(string $key);
}
