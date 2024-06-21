<?php

namespace Oro\Bundle\FedexShippingBundle\Client\RateService\Request\Factory;

use Oro\Bundle\FedexShippingBundle\Client\Request\Factory\FedexRequestByIntegrationSettingsFactoryInterface;
use Oro\Bundle\FedexShippingBundle\Client\Request\FedexRequest;
use Oro\Bundle\FedexShippingBundle\Client\Request\FedexRequestInterface;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\ShippingBundle\Provider\ShippingOriginProvider;

/**
 * The factory to create FedEx shipment validation request.
 */
class FedexRateServiceValidateConnectionRequestFactory implements FedexRequestByIntegrationSettingsFactoryInterface
{
    /**
     * @var ShippingOriginProvider
     */
    private $shippingOriginProvider;

    public function __construct(ShippingOriginProvider $shippingOriginProvider)
    {
        $this->shippingOriginProvider = $shippingOriginProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function create(FedexIntegrationSettings $settings): FedexRequestInterface
    {
        $shippingOrigin = $this->shippingOriginProvider->getSystemShippingOrigin();

        return new FedexRequest(
            '/rate/v1/rates/quotes',
            [
                'accountNumber' => [
                    'value' => $settings->getAccountNumber()
                ],
                'requestedShipment' => [
                    "rateRequestType" => ["ACCOUNT"],
                    'pickupType' => $settings->getPickupType(),
                    'shipper' => [
                        'address' => [
                            'streetLines' => [
                                $shippingOrigin->getStreet(),
                            ],
                            'city' => $shippingOrigin->getCity(),
                            'stateOrProvinceCode' => $shippingOrigin->getRegionCode(),
                            'postalCode' => $shippingOrigin->getPostalCode(),
                            'countryCode' => $shippingOrigin->getCountryIso2(),
                        ],
                    ],
                    'recipient' => [
                        'address' => [
                            'streetLines' => [
                                $shippingOrigin->getStreet(),
                            ],
                            'city' => $shippingOrigin->getCity(),
                            'stateOrProvinceCode' => $shippingOrigin->getRegionCode(),
                            'postalCode' => $shippingOrigin->getPostalCode(),
                            'countryCode' => $shippingOrigin->getCountryIso2(),
                        ],
                    ],
                    'totalPackageCount' => 1,
                    'requestedPackageLineItems' => [
                        0 => [
                            'groupPackageCount' => 1,
                            'weight' => [
                                'value' => 10,
                                'units' => $settings->getUnitOfWeight(),
                            ],
                            'dimensions' => [
                                'length' => 5,
                                'width' => 10,
                                'height' => 10,
                                'units' => $settings->getDimensionsUnit(),
                            ],
                        ],
                    ],
                ],
            ],
            true
        );
    }
}
