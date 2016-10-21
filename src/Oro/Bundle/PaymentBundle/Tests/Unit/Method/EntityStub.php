<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Method;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsAwareInterface;

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
     * @return AbstractAddress
     */
    public function getShippingAddress()
    {
        return $this->shippingAddress;
    }
}
