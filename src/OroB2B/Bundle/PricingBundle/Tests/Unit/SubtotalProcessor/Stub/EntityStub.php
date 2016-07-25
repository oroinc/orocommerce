<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Stub;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\CurrencyBundle\Entity\CurrencyAwareInterface;

use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsAwareInterface;

class EntityStub implements LineItemsAwareInterface, CurrencyAwareInterface
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
     * @return ArrayCollection
     */
    public function getLineItems()
    {
        return $this->lineItems;
    }

    /**
     * @param LineItemStub $lineItem
     * @return EntityStub
     */
    public function addLineItem(LineItemStub $lineItem)
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

    /**
     * @return float
     */
    public function getSubtotal()
    {
        return 12345.0;
    }
}
