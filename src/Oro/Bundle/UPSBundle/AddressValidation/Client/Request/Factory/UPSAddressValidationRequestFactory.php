<?php

namespace Oro\Bundle\UPSBundle\AddressValidation\Client\Request\Factory;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\AddressValidationBundle\Client\Request\AddressValidationRequest;
use Oro\Bundle\AddressValidationBundle\Client\Request\AddressValidationRequestInterface;
use Oro\Bundle\AddressValidationBundle\Client\Request\Factory\AddressValidationRequestFactoryInterface;
use Oro\Bundle\UPSBundle\AddressValidation\Client\UPSAddressValidationClient;

/**
 * Create UPS Address Validation request by a given context.
 */
class UPSAddressValidationRequestFactory implements AddressValidationRequestFactoryInterface
{
    public function create(AbstractAddress $address): AddressValidationRequestInterface
    {
        $requestData = $this->buildRequestData($address);

        return new AddressValidationRequest($this->getRequestUri(), $requestData);
    }

    private function buildRequestData(AbstractAddress $address): array
    {
        return [
            'XAVRequest' => [
                'AddressKeyFormat' => [
                    'AddressLine' => [
                        $address->getStreet(),
                        $address->getStreet2(),
                    ],
                    'PoliticalDivision2' => $address->getCity(),
                    'PoliticalDivision1' => $address->getRegionCode(),
                    'PostcodePrimaryLow' => $address->getPostalCode(),
                    'CountryCode' => $address->getCountryIso2(),
                ],
            ],
        ];
    }

    protected function getRequestUri(): string
    {
        return UPSAddressValidationClient::ADDRESS_VALIDATION_URI . $this->getRequestOption();
    }

    protected function getRequestOption(): int
    {
        return UPSAddressValidationClient::REQUEST_OPTION_ADDRESS_VALIDATION;
    }
}
