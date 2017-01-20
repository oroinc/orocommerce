<?php

namespace Oro\Bundle\DPDBundle\Context;

use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;

// FIXME: Talk with David regarding the missing DPDShippingContextInterface methods
class DPDShippingContext implements DPDShippingContextInterface
{
    /**
     * @var ShippingContextInterface
     */
    protected $shippingContext;

    public function __construct(ShippingContextInterface $shippingContext)
    {
        $this->shippingContext = $shippingContext;
    }

    /**
     * @inheritDoc
     */
    public function getCustomer()
    {
        return $this->shippingContext->getCustomer();
    }

    /**
     * @inheritDoc
     */
    public function getCustomerUser()
    {
        return $this->shippingContext->getCustomerUser();
    }

    /**
     * @inheritDoc
     */
    public function getShipDate()
    {
        // TODO: Implement getShippingDate() method.
    }

    /**
     * @inheritDoc
     */
    public function getLineItems()
    {
        return $this->shippingContext->getLineItems();
    }

    /**
     * @inheritDoc
     */
    public function getBillingAddress()
    {
        return $this->shippingContext->getBillingAddress();
    }

    /**
     * @inheritDoc
     */
    public function getShippingAddress()
    {
        return $this->shippingContext->getShippingAddress();
    }

    /**
     * @inheritDoc
     */
    public function getShippingOrigin()
    {
        return $this->shippingContext->getShippingOrigin();
    }

    /**
     * @inheritDoc
     */
    public function getPaymentMethod()
    {
        return $this->shippingContext->getPaymentMethod();
    }

    /**
     * @inheritDoc
     */
    public function getCurrency()
    {
        return $this->shippingContext->getCurrency();
    }

    /**
     * @inheritDoc
     */
    public function getSubtotal()
    {
        return $this->shippingContext->getSubtotal();
    }

    /**
     * @inheritDoc
     */
    public function getSourceEntity()
    {
        return $this->shippingContext->getSourceEntity();
    }

    /**
     * @inheritDoc
     */
    public function getSourceEntityIdentifier()
    {
        return $this->shippingContext->getSourceEntityIdentifier();
    }
}
