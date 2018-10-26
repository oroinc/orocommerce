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

    /**
     * {@inheritdoc}
     */
    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    /**
     * {@inheritdoc}
     */
    public function setCustomer(Customer $customer = null)
    {
        $this->customer = $customer;
    }

    /**
     * {@inheritdoc}
     */
    public function getWebsite(): ?Website
    {
        return $this->website;
    }

    /**
     * {@inheritdoc}
     */
    public function setWebsite(Website $website = null)
    {
        $this->website = $website;
    }

    /**
     * {@inheritdoc}
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * {@inheritdoc}
     */
    public function setContext($context)
    {
        $this->context = $context;
    }

    /**
     * {@inheritdoc}
     */
    public function getData(string $key)
    {
        if (\array_key_exists($key, $this->data)) {
            return $this->data[$key];
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function setData(string $key, $value)
    {
        $this->data[$key] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function unsetData(string $key)
    {
        unset($this->data[$key]);
    }
}
