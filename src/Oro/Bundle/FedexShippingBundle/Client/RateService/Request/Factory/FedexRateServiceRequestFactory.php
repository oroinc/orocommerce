<?php

namespace Oro\Bundle\FedexShippingBundle\Client\RateService\Request\Factory;

use Oro\Bundle\FedexShippingBundle\Client\Request\Factory\FedexRequestFactoryInterface;
use Oro\Bundle\FedexShippingBundle\Client\Request\FedexRequest;
use Oro\Bundle\FedexShippingBundle\Client\Request\FedexRequestInterface;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;

class FedexRateServiceRequestFactory implements FedexRequestFactoryInterface
{
    /**
     * @var SymmetricCrypterInterface
     */
    private $crypter;

    /**
     * @var FedexRequestFactoryInterface
     */
    private $lineItemsFactory;

    /**
     * @param SymmetricCrypterInterface    $crypter
     * @param FedexRequestFactoryInterface $lineItemsFactory
     */
    public function __construct(
        SymmetricCrypterInterface $crypter,
        FedexRequestFactoryInterface $lineItemsFactory
    ) {
        $this->crypter = $crypter;
        $this->lineItemsFactory = $lineItemsFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function create(
        FedexIntegrationSettings $settings,
        ShippingContextInterface $context
    ): FedexRequestInterface {
        return new FedexRequest([
            'WebAuthenticationDetail' => [
                'UserCredential' => [
                    'Key' => $settings->getKey(),
                    'Password' => $this->crypter->decryptData($settings->getPassword()),
                ]
            ],
            'ClientDetail' => [
                'AccountNumber' => $settings->getAccountNumber(),
                'MeterNumber' => $settings->getMeterNumber(),
            ],
            'Version' => [
                'ServiceId' => 'crs',
                'Major' => '20',
                'Intermediate' => '0',
                'Minor' => '0'
            ],
            'RequestedShipment' => [
                'DropoffType' => $settings->getPickupType(),
                'Shipper' => [
                    'StreetLines' => [
                        $context->getShippingAddress()->getStreet(),
                        $context->getShippingAddress()->getStreet2(),
                    ],
                    'City' => $context->getShippingAddress()->getCity(),
                    'StateOrProvinceCode' => $context->getShippingAddress()->getRegionCode(),
                    'PostalCode' => $context->getShippingAddress()->getPostalCode(),
                    'CountryCode' => $context->getShippingAddress()->getCountryIso2(),
                ],
                'Recipient' => [
                    'StreetLines' => [
                        $context->getShippingOrigin()->getStreet(),
                        $context->getShippingOrigin()->getStreet2(),
                    ],
                    'City' => $context->getShippingOrigin()->getCity(),
                    'StateOrProvinceCode' => $context->getShippingOrigin()->getRegionCode(),
                    'PostalCode' => $context->getShippingOrigin()->getPostalCode(),
                    'CountryCode' => $context->getShippingOrigin()->getCountryIso2(),
                ],
                'RequestedPackageLineItems' => $this->lineItemsFactory->create($settings, $context)->getRequestData(),
            ],
        ]);
    }
}
