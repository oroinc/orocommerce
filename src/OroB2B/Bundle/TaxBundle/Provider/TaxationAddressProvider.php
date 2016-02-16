<?php

namespace OroB2B\Bundle\TaxBundle\Provider;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;

use OroB2B\Bundle\TaxBundle\Model\TaxBaseExclusion;
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
     * @param AbstractAddress $billingAddress
     * @param AbstractAddress $shippingAddress
     * @return AbstractAddress
     */
    public function getAddressForTaxation(
        AbstractAddress $billingAddress = null,
        AbstractAddress $shippingAddress = null
    ) {
        $address = $this->getDestinationAddress($billingAddress, $shippingAddress);
        $isOriginBaseByDefaultAddressType = $this->settingsProvider->isOriginBaseByDefaultAddressType();

        if (null === $address) {
            return $isOriginBaseByDefaultAddressType ? $this->getOriginAddress() : $address;
        }

        $exclusion = $this->getExclusions($address);
        if ($exclusion) {
            return $exclusion->getOption() === TaxationSettingsProvider::USE_AS_BASE_SHIPPING_ORIGIN ?
                $this->getOriginAddress() : $address;
        }

        return $isOriginBaseByDefaultAddressType ? $this->getOriginAddress() : $address;
    }

    /**
     * @param AbstractAddress $address
     * @return TaxBaseExclusion
     */
    protected function getExclusions(AbstractAddress $address)
    {
        $exclusions = $this->settingsProvider->getBaseAddressExclusions();
        foreach ($exclusions as $exclusion) {
            if ($address->getCountry()->getIso2Code() !== $exclusion->getCountry()->getIso2Code()) {
                continue;
            }

            if ($address->getRegionText() && $address->getRegionText() !== $exclusion->getRegionText()) {
                continue;
            }

            if ($address->getRegion()
                && $exclusion->getRegion()->getCombinedCode() !== $address->getRegion()->getCombinedCode()
            ) {
                continue;
            }

            return $exclusion;
        }

        return null;
    }

    /**
     * Get address by config setting (shipping or billing)
     *
     * @param AbstractAddress $billingAddress
     * @param AbstractAddress $shippingAddress
     * @return null|AbstractAddress
     */
    protected function getDestinationAddress(
        AbstractAddress $billingAddress = null,
        AbstractAddress $shippingAddress = null
    ) {
        $type = $this->settingsProvider->getDestination();
        if ($type === TaxationSettingsProvider::DESTINATION_BILLING_ADDRESS) {
            return $billingAddress;
        } elseif ($type === TaxationSettingsProvider::DESTINATION_SHIPPING_ADDRESS) {
            return $shippingAddress;
        }

        return null;
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
        if ($countryCode === 'US') {
            $digitalProductTaxCodes = $this->settingsProvider->getDigitalProductsTaxCodesUS();
        } elseif ($this->countryMatcher->isEuropeanUnionCountry($countryCode)) {
            $digitalProductTaxCodes = $this->settingsProvider->getDigitalProductsTaxCodesEU();
        } else {
            return false;
        }

        return in_array($taxCode, $digitalProductTaxCodes, true);
    }
}
