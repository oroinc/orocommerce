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
     * @var Customer
     */
    protected $customer;

    /**
     * @var Website
     */
    protected $website;

    /**
     * @var object
     */
    protected $context;

    /**
     * @var array
     */
    protected $data = [];

    /**
     * {@inheritdoc}
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     * @param Customer|null $customer
     * @return ProductPriceScopeCriteria
     */
    public function setCustomer(Customer $customer = null)
    {
        $this->customer = $customer;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getWebsite(): Website
    {
        return $this->website;
    }

    /**
     * @param Website|null $website
     * @return ProductPriceScopeCriteria
     */
    public function setWebsite(Website $website = null)
    {
        $this->website = $website;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @param object|null $context
     * @return ProductPriceScopeCriteria
     */
    public function setContext($context)
    {
        $this->context = $context;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getData($key)
    {
        if (array_key_exists($key, $this->data)) {
            return $this->data[$key];
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function setData($key, $value)
    {
        $this->data[$key] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function unsetData($key)
    {
        unset($this->data[$key]);
    }
}
