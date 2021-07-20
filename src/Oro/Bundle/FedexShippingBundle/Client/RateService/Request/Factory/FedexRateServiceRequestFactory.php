<?php

namespace Oro\Bundle\FedexShippingBundle\Client\RateService\Request\Factory;

use Oro\Bundle\FedexShippingBundle\Client\RateService\Request\Settings\FedexRateServiceRequestSettingsInterface;
use Oro\Bundle\FedexShippingBundle\Client\Request\FedexRequest;
use Oro\Bundle\FedexShippingBundle\Factory\FedexPackagesByLineItemsAndPackageSettingsFactoryInterface;
use Oro\Bundle\FedexShippingBundle\Factory\FedexPackageSettingsByIntegrationSettingsAndRuleFactoryInterface;
use Oro\Bundle\FedexShippingBundle\Modifier\ShippingLineItemCollectionBySettingsModifierInterface;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;

/**
 * Create Rate Service request by a given context and settings.
 */
class FedexRateServiceRequestFactory implements FedexRequestByRateServiceSettingsFactoryInterface
{
    /**
     * @var SymmetricCrypterInterface
     */
    private $crypter;

    /**
     * @var FedexPackageSettingsByIntegrationSettingsAndRuleFactoryInterface
     */
    private $packageSettingsFactory;

    /**
     * @var FedexPackagesByLineItemsAndPackageSettingsFactoryInterface
     */
    private $packagesFactory;

    /**
     * @var ShippingLineItemCollectionBySettingsModifierInterface
     */
    private $convertToFedexUnitsModifier;

    public function __construct(
        SymmetricCrypterInterface $crypter,
        FedexPackageSettingsByIntegrationSettingsAndRuleFactoryInterface $packageSettingsFactory,
        FedexPackagesByLineItemsAndPackageSettingsFactoryInterface $packagesFactory,
        ShippingLineItemCollectionBySettingsModifierInterface $convertToFedexUnitsModifier
    ) {
        $this->crypter = $crypter;
        $this->packageSettingsFactory = $packageSettingsFactory;
        $this->packagesFactory = $packagesFactory;
        $this->convertToFedexUnitsModifier = $convertToFedexUnitsModifier;
    }

    /**
     * {@inheritDoc}
     */
    public function create(FedexRateServiceRequestSettingsInterface $settings)
    {
        $context = $settings->getShippingContext();
        $packageSettings = $this->packageSettingsFactory->create(
            $settings->getIntegrationSettings(),
            $settings->getShippingServiceRule()
        );

        $lineItems = $this->convertToFedexUnitsModifier->modify(
            $context->getLineItems(),
            $settings->getIntegrationSettings()
        );

        $packages = $this->packagesFactory->create($lineItems, $packageSettings);
        if (empty($packages)) {
            return null;
        }

        if (!$context->getShippingOrigin() || !$context->getShippingAddress()) {
            return null;
        }

        $requestData = [
            'WebAuthenticationDetail' => [
                'UserCredential' => [
                    'Key' => $settings->getIntegrationSettings()->getKey(),
                    'Password' => $this->crypter->decryptData($settings->getIntegrationSettings()->getPassword()),
                ]
            ],
            'ClientDetail' => [
                'AccountNumber' => $settings->getIntegrationSettings()->getAccountNumber(),
                'MeterNumber' => $settings->getIntegrationSettings()->getMeterNumber(),
            ],
            'Version' => [
                'ServiceId' => 'crs',
                'Major' => '20',
                'Intermediate' => '0',
                'Minor' => '0'
            ],
            'RequestedShipment' => [
                'DropoffType' => $settings->getIntegrationSettings()->getPickupType(),
                'Shipper' => [
                    'Address' => [
                        'StreetLines' => [
                            $context->getShippingOrigin()->getStreet(),
                            $context->getShippingOrigin()->getStreet2(),
                        ],
                        'City' => $context->getShippingOrigin()->getCity(),
                        'StateOrProvinceCode' => $context->getShippingOrigin()->getRegionCode(),
                        'PostalCode' => $context->getShippingOrigin()->getPostalCode(),
                        'CountryCode' => $context->getShippingOrigin()->getCountryIso2(),
                    ],
                ],
                'Recipient' => [
                    'Address' => [
                        'StreetLines' => [
                            $context->getShippingAddress()->getStreet(),
                            $context->getShippingAddress()->getStreet2(),
                        ],
                        'City' => $context->getShippingAddress()->getCity(),
                        'StateOrProvinceCode' => $context->getShippingAddress()->getRegionCode(),
                        'PostalCode' => $context->getShippingAddress()->getPostalCode(),
                        'CountryCode' => $context->getShippingAddress()->getCountryIso2(),
                    ],
                ],
                'PackageCount' => count($packages),
                'RequestedPackageLineItems' => $packages,
            ],
        ];

        if ($settings->getShippingServiceRule()->getServiceType()) {
            $requestData['RequestedShipment']['ServiceType'] = $settings->getShippingServiceRule()->getServiceType();
        }

        if ($settings->getShippingServiceRule()->isResidentialAddress()) {
            $requestData['RequestedShipment']['Recipient']['Address']['Residential'] = true;
        }

        return new FedexRequest($requestData);
    }
}
