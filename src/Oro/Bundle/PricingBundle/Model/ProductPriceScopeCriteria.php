<?php

namespace Oro\Bundle\PricingBundle\Model;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * Store criteria data required for searching of product price.
 */
class ProductPriceScopeCriteria implements ProductPriceScopeCriteriaInterface
{
    /**
     * @var Customer|null
     */
    protected $customer;

    /**
     * @var Website|null
     */
    protected $website;

    /**
     * @var object|null
     */
    protected $context;

    /**
     * @var array
     */
    protected $data = [];

    #[\Override]
    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    #[\Override]
    public function setCustomer(?Customer $customer = null)
    {
        $this->customer = $customer;
    }

    #[\Override]
    public function getWebsite(): ?Website
    {
        return $this->website;
    }

    #[\Override]
    public function setWebsite(?Website $website = null)
    {
        $this->website = $website;
    }

    #[\Override]
    public function getContext()
    {
        return $this->context;
    }

    #[\Override]
    public function setContext($context)
    {
        $this->context = $context;
    }

    #[\Override]
    public function getData(string $key)
    {
        if (\array_key_exists($key, $this->data)) {
            return $this->data[$key];
        }

        return null;
    }

    #[\Override]
    public function setData(string $key, $value)
    {
        $this->data[$key] = $value;
    }

    #[\Override]
    public function unsetData(string $key)
    {
        unset($this->data[$key]);
    }
}
