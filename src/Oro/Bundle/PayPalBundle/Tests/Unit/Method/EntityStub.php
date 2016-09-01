<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Method;

use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsAwareInterface;

use Doctrine\Common\Collections\ArrayCollection;

class EntityStub implements LineItemsAwareInterface
{
    /** @var \stdClass */
    protected $shippingAddress;

    /**
     * Fill shippingAddress field with dummy object
     */
    public function __construct()
    {
        $this->shippingAddress = new \stdClass();
    }

    /**
     * {@inheritdoc}
     */
    public function getLineItems()
    {
        return new ArrayCollection([]);
    }

    /**
     * @return \stdClass
     */
    public function getShippingAddress()
    {
        return $this->shippingAddress;
    }
}
