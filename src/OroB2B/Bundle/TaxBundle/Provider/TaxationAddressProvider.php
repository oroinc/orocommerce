<?php

namespace OroB2B\Bundle\TaxBundle\Provider;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderAddress;
use OroB2B\Bundle\TaxBundle\Matcher\CountryMatcher;

class TaxationAddressProvider
{
    /**
     * @var CountryMatcher
     */
    protected $countryMatcher;

    /**
     * @param TaxationSettingsProvider $settingsProvider
     * @param CountryMatcher $countryMatcher
     */
    public function __construct(TaxationSettingsProvider $settingsProvider, CountryMatcher $countryMatcher)
    {
        $this->settingsProvider = $settingsProvider;
        $this->countryMatcher = $countryMatcher;
    }

    /**
     * @param Order $order
     * @return AbstractAddress
     */
    public function getAddressForTaxation(Order $order)
    {
        $orderAddress = $this->getDestinationAddress($order);

        if (null === $orderAddress) {
            return null;
        }

        $exclusionUsed = false;
        $exclusions = $this->settingsProvider->getBaseAddressExclusions();
        foreach ($exclusions as $exclusion) {
            if ($orderAddress->getCountry() === $exclusion->getCountry() &&
                ($exclusion->getRegion() === null || $exclusion->getRegion() === $orderAddress->getRegion())
            ) {
                if ($exclusion->getOption() === TaxationSettingsProvider::USE_AS_BASE_SHIPPING_ORIGIN) {
                    $orderAddress = $this->getOriginAddress();
                }

                $exclusionUsed = true;
                break;
            }
        }

        if (!$exclusionUsed && $this->settingsProvider->isOriginBaseByDefaultAddressType()) {
            return $this->getOriginAddress();
        }

        return $orderAddress;
    }

    /**
     * Get address by config setting (shipping or billing)
     *
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
        switch ($type) {
            case TaxationSettingsProvider::DESTINATION_BILLING_ADDRESS:
                $orderAddress = $order->getBillingAddress();
                break;
            case TaxationSettingsProvider::DESTINATION_SHIPPING_ADDRESS:
                $orderAddress = $order->getShippingAddress();
                break;
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

    /**
     * Check is tax code is digital in specified country
     *
     * @param string $countryCode
     * @param string $taxCode
     * @return bool
     */
    public function isDigitalProductTaxCode($countryCode, $taxCode)
    {
        if ($this->countryMatcher->isEuropeanUnionCountry($countryCode)) {
            $digitalProductTaxCodes = $this->settingsProvider->getDigitalProductsTaxCodesEU();
        } elseif ($countryCode === 'US') {
            $digitalProductTaxCodes = $this->settingsProvider->getDigitalProductsTaxCodesUS();
        } else {
            return false;
        }

        return in_array($taxCode, $digitalProductTaxCodes, true);
    }
}
