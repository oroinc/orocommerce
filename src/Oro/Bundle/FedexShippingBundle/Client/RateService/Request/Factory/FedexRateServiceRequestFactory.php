<?php

namespace Oro\Bundle\FedexShippingBundle\Client\RateService\Request\Factory;

use Oro\Bundle\FedexShippingBundle\Client\RateService\Request\Settings\FedexRateServiceRequestSettingsInterface;
use Oro\Bundle\FedexShippingBundle\Client\Request\FedexRequest;
use Oro\Bundle\FedexShippingBundle\Factory\FedexPackagesByLineItemsAndPackageSettingsFactoryInterface;
use Oro\Bundle\FedexShippingBundle\Factory\FedexPackageSettingsByIntegrationSettingsAndRuleFactoryInterface;
use Oro\Bundle\FedexShippingBundle\Modifier\ShippingLineItemCollectionBySettingsModifierInterface;

/**
 * Create Rate Service request by a given context and settings.
 */
class FedexRateServiceRequestFactory implements FedexRequestByRateServiceSettingsFactoryInterface
{
    private FedexPackageSettingsByIntegrationSettingsAndRuleFactoryInterface $packageSettingsFactory;
    private FedexPackagesByLineItemsAndPackageSettingsFactoryInterface $packagesFactory;
    private ShippingLineItemCollectionBySettingsModifierInterface $convertToFedexUnitsModifier;

    public function __construct(
        FedexPackageSettingsByIntegrationSettingsAndRuleFactoryInterface $packageSettingsFactory,
        FedexPackagesByLineItemsAndPackageSettingsFactoryInterface $packagesFactory,
        ShippingLineItemCollectionBySettingsModifierInterface $convertToFedexUnitsModifier
    ) {
        $this->packageSettingsFactory = $packageSettingsFactory;
        $this->packagesFactory = $packagesFactory;
        $this->convertToFedexUnitsModifier = $convertToFedexUnitsModifier;
    }

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
            'accountNumber' => [
                'value' => $settings->getIntegrationSettings()->getAccountNumber()
            ],
            'requestedShipment' => [
                "rateRequestType" => ["ACCOUNT"],
                'pickupType' => $settings->getIntegrationSettings()->getPickupType(),
                'shipper' => [
                    'address' => [
                        'streetLines' => [
                            $context->getShippingOrigin()->getStreet(),
                            $context->getShippingOrigin()->getStreet2(),
                        ],
                        'city' => $context->getShippingOrigin()->getCity(),
                        'stateOrProvinceCode' => $context->getShippingOrigin()->getRegionCode(),
                        'postalCode' => $context->getShippingOrigin()->getPostalCode(),
                        'countryCode' => $context->getShippingOrigin()->getCountryIso2(),
                    ],
                ],
                'recipient' => [
                    'address' => [
                        'streetLines' => [
                            $context->getShippingAddress()->getStreet(),
                            $context->getShippingAddress()->getStreet2(),
                        ],
                        'city' => $context->getShippingAddress()->getCity(),
                        'stateOrProvinceCode' => $context->getShippingAddress()->getRegionCode(),
                        'postalCode' => $context->getShippingAddress()->getPostalCode(),
                        'countryCode' => $context->getShippingAddress()->getCountryIso2(),
                    ],
                ],
                'totalPackageCount' => count($packages),
                'requestedPackageLineItems' => $packages,
            ],
        ];

        if ($settings->getShippingServiceRule()->getServiceType()) {
            $requestData['requestedShipment']['serviceType'] = $settings->getShippingServiceRule()->getServiceType();
        }

        if ($settings->getShippingServiceRule()->isResidentialAddress()) {
            $requestData['requestedShipment']['recipient']['address']['residential'] = true;
        }

        return new FedexRequest('/rate/v1/rates/quotes', $requestData);
    }
}
