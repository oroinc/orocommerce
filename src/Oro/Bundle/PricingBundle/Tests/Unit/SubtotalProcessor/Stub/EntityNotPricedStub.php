<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Stub;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerAwareInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsNotPricedAwareInterface;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Entity\WebsiteBasedCurrencyAwareInterface;

class EntityNotPricedStub implements
    LineItemsNotPricedAwareInterface,
    CustomerAwareInterface,
    WebsiteBasedCurrencyAwareInterface
{
    /**
     * @var ArrayCollection
     */
    protected $lineItems;

    /**
     * @var string
     */
    protected $currency;

    /**
     * @var Customer
     */
    protected $customer;

    /**
     * @var Website
     */
    protected $website;

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        $this->lineItems = new ArrayCollection();
    }

    /**
     * @return ArrayCollection
     */
    public function getLineItems()
    {
        return $this->lineItems;
    }

    /**
     * @param LineItemNotPricedStub $lineItem
     *
     * @return EntityStub
     */
    public function addLineItem(LineItemNotPricedStub $lineItem)
    {
        $this->lineItems[] = $lineItem;

        return $this;
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     *
     * @return $this
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * @return Customer
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     * @param Customer $customer
     *
     * @return $this
     */
    public function setCustomer(Customer $customer)
    {
        $this->customer = $customer;

        return $this;
    }

    /**
     * @return Website
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * @param Website $website
     *
     * @return $this
     */
    public function setWebsite(Website $website)
    {
        $this->website = $website;

        return $this;
    }
}
