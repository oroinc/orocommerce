<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Stub;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\CurrencyBundle\Entity\CurrencyAwareInterface;

use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsNotPricedAwareInterface;

class EntityNotPricedStub implements LineItemsNotPricedAwareInterface, CurrencyAwareInterface
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
     * @return $this
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }
}
