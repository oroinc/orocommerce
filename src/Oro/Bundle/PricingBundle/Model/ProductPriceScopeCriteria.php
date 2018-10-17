<?php

namespace Oro\Bundle\PricingBundle\Model;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\WebsiteBundle\Entity\Website;

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
     * @var CustomerGroup
     */
    protected $customerGroup;

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
     * @param Customer $customer
     * @return ProductPriceScopeCriteria
     */
    public function setCustomer(Customer $customer)
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
     * @param Website $website
     * @return ProductPriceScopeCriteria
     */
    public function setWebsite(Website $website)
    {
        $this->website = $website;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCustomerGroup()
    {
        return $this->customerGroup;
    }

    /**
     * @param CustomerGroup $customerGroup
     * @return ProductPriceScopeCriteria
     */
    public function setCustomerGroup(CustomerGroup $customerGroup)
    {
        $this->customerGroup = $customerGroup;

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
