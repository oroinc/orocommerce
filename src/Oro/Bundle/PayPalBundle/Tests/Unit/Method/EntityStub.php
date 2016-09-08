<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Method;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsAwareInterface;

use Doctrine\Common\Collections\ArrayCollection;

class EntityStub implements LineItemsAwareInterface
{
    /** @var AbstractAddress */
    protected $shippingAddress;

    /**
     * Fill shippingAddress field with dummy object
     * @param AbstractAddress $abstractAddress
     */
    public function __construct(AbstractAddress $abstractAddress)
    {
        $this->shippingAddress = $abstractAddress;
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
