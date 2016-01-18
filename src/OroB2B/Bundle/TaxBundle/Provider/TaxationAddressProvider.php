<?php

namespace OroB2B\Bundle\TaxBundle\Provider;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderAddress;

class TaxationAddressProvider
{
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
    public function getAddressForTaxation(Order $order)
    {
        if ($this->settingsProvider->isOriginDefaultAddressType()) {
            return $this->settingsProvider->getOrigin();
        }

        $orderAddress = $this->getDestinationAddress($order);

        if (null === $orderAddress) {
            return null;
        }

        // TODO: Check $orderAddress on base exceptions
        if (false) {
            // $exceptionAddressType have to get from found exception
            $exceptionAddressType = TaxationSettingsProvider::DESTINATION_BILLING_ADDRESS;

            $orderAddress = $this->getDestinationAddressByType($order, $exceptionAddressType);
        }
        return $orderAddress;
    }

    /**
     * @param Order $order
     * @return OrderAddress|null
     */
    protected function getDestinationAddress(Order $order)
    {
        return $this->getDestinationAddressByType($order, $this->settingsProvider->getDestination());
    }

    /**
     * @param Order $order
     * @param string $type
     * @return OrderAddress|null
     */
    protected function getDestinationAddressByType(Order $order, $type)
    {
        $orderAddress = null;
        if ($type === TaxationSettingsProvider::DESTINATION_BILLING_ADDRESS) {
            $orderAddress = $order->getBillingAddress();
        } elseif ($type === TaxationSettingsProvider::DESTINATION_SHIPPING_ADDRESS) {
            $orderAddress = $order->getShippingAddress();
        }

        return $orderAddress;
    }

    /**
     * @return AbstractAddress
     */
    public function getOriginAddress()
    {
        return $this->settingsProvider->getOrigin();
    }
}
