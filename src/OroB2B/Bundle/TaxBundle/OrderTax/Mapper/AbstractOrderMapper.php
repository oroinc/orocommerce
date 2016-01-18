<?php

namespace OroB2B\Bundle\TaxBundle\OrderTax\Mapper;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\TaxBundle\Mapper\TaxMapperInterface;
use OroB2B\Bundle\TaxBundle\Provider\TaxationSettingsProvider;

abstract class AbstractOrderMapper implements TaxMapperInterface
{
    /**
     * @var TaxationSettingsProvider
     */
    protected $settingsProvider;

    /**
     * @param TaxationSettingsProvider $settingsProvider
     */
    public function __construct(TaxationSettingsProvider $settingsProvider)
    {
        $this->settingsProvider = $settingsProvider;
    }

    /**
     * @param Order $order
     * @return AbstractAddress
     */
    public function getOrderAddress(Order $order)
    {
        return $this->settingsProvider->isBillingAddressDestination() ?
            $order->getBillingAddress() : $order->getShippingAddress();
    }
}
