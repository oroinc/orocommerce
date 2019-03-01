<?php

namespace Oro\Bundle\PricingBundle\Model\DTO;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * DTO to store Price list relations used to trigger MQ message
 */
class PriceListRelationTrigger
{
    const WEBSITE = 'website';
    const ACCOUNT = 'customer';
    const ACCOUNT_GROUP = 'customerGroup';
    const FORCE = 'force';

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
     * @var bool
     */
    protected $force = false;

    /**
     * @return Customer|null
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     * @param Customer|null $customer
     * @return $this
     */
    public function setCustomer(Customer $customer = null)
    {
        $this->customer = $customer;

        return $this;
    }

    /**
     * @return Website|null
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * @param Website|null $website
     * @return $this
     */
    public function setWebsite(Website $website = null)
    {
        $this->website = $website;

        return $this;
    }

    /**
     * @return CustomerGroup|null
     */
    public function getCustomerGroup()
    {
        return $this->customerGroup;
    }

    /**
     * @param CustomerGroup|null $customerGroup
     * @return $this
     */
    public function setCustomerGroup(CustomerGroup $customerGroup = null)
    {
        $this->customerGroup = $customerGroup;

        return $this;
    }

    /**
     * @return bool
     */
    public function isForce()
    {
        return $this->force;
    }

    /**
     * @param bool $force
     * @return $this
     */
    public function setForce($force)
    {
        $this->force = (bool)$force;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            self::WEBSITE => $this->website,
            self::ACCOUNT => $this->customer,
            self::ACCOUNT_GROUP => $this->customerGroup,
            self::FORCE => $this->force,
        ];
    }
}
