<?php

namespace Oro\Bundle\FedexShippingBundle\AddressValidation\Client\Request\Factory;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\AddressValidationBundle\Client\Request\AddressValidationRequest;
use Oro\Bundle\AddressValidationBundle\Client\Request\AddressValidationRequestInterface;
use Oro\Bundle\AddressValidationBundle\Client\Request\Factory\AddressValidationRequestFactoryInterface;
use Oro\Bundle\FedexShippingBundle\AddressValidation\Client\FedexAddressValidationClient;

/**
 * Creates FedEx Address Validation request by a given context.
 */
class FedexAddressValidationRequestFactory implements AddressValidationRequestFactoryInterface
{
    public function create(AbstractAddress $address): AddressValidationRequestInterface
    {
        $requestData = $this->buildRequestData($address);

        return new AddressValidationRequest(FedexAddressValidationClient::ADDRESS_VALIDATION_URI, $requestData);
    }

    private function buildRequestData(AbstractAddress $address): array
    {
        return [
            'addressesToValidate' => [
                [
                    'address' => [
                        'streetLines' => [
                            $address->getStreet(),
                            $address->getStreet2(),
                        ],
                        'city' => $address->getCity(),
                        'stateOrProvinceCode' => $address->getRegionCode(),
                        'postalCode' => $address->getPostalCode(),
                        'countryCode' => $address->getCountryIso2(),
                    ],
                ],
            ],
        ];
    }
}
