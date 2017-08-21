<?php

namespace Oro\Bundle\FedexShippingBundle\Client\RateService\Request\Factory;

use Oro\Bundle\FedexShippingBundle\Client\Request\Factory\FedexRequestByContextAndSettingsFactoryInterface;
use Oro\Bundle\FedexShippingBundle\Client\Request\FedexRequest;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\Factory\FedexPackagesByLineItemsAndPackageSettingsFactoryInterface;
use Oro\Bundle\FedexShippingBundle\Factory\FedexPackageSettingsByIntegrationSettingsFactoryInterface;
use Oro\Bundle\FedexShippingBundle\Modifier\ShippingLineItemCollectionBySettingsModifierInterface;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Modifier\ShippingLineItemCollectionModifierInterface;

class FedexRateServiceRequestFactory implements FedexRequestByContextAndSettingsFactoryInterface
{
    /**
     * @var SymmetricCrypterInterface
     */
    private $crypter;

    /**
     * @var FedexPackageSettingsByIntegrationSettingsFactoryInterface
     */
    private $packageSettingsFactory;

    /**
     * @var FedexPackagesByLineItemsAndPackageSettingsFactoryInterface
     */
    private $packagesFactory;

    /**
     * @var ShippingLineItemCollectionModifierInterface
     */
    private $addProductOptionsModifier;

    /**
     * @var ShippingLineItemCollectionBySettingsModifierInterface
     */
    private $convertToFedexUnitsModifier;

    /**
     * @param SymmetricCrypterInterface                                  $crypter
     * @param FedexPackageSettingsByIntegrationSettingsFactoryInterface  $packageSettingsFactory
     * @param FedexPackagesByLineItemsAndPackageSettingsFactoryInterface $packagesFactory
     * @param ShippingLineItemCollectionModifierInterface                $addProductOptionsModifier
     * @param ShippingLineItemCollectionBySettingsModifierInterface      $convertToFedexUnitsModifier
     */
    public function __construct(
        SymmetricCrypterInterface $crypter,
        FedexPackageSettingsByIntegrationSettingsFactoryInterface $packageSettingsFactory,
        FedexPackagesByLineItemsAndPackageSettingsFactoryInterface $packagesFactory,
        ShippingLineItemCollectionModifierInterface $addProductOptionsModifier,
        ShippingLineItemCollectionBySettingsModifierInterface $convertToFedexUnitsModifier
    ) {
        $this->crypter = $crypter;
        $this->packageSettingsFactory = $packageSettingsFactory;
        $this->packagesFactory = $packagesFactory;
        $this->addProductOptionsModifier = $addProductOptionsModifier;
        $this->convertToFedexUnitsModifier = $convertToFedexUnitsModifier;
    }

    /**
     * {@inheritDoc}
     */
    public function create(FedexIntegrationSettings $settings, ShippingContextInterface $context)
    {
        $packageSettings = $this->packageSettingsFactory->create($settings);

        $lineItems = $this->convertToFedexUnitsModifier->modify(
            $this->addProductOptionsModifier->modify($context->getLineItems()),
            $settings
        );

        $packages = $this->packagesFactory->create($lineItems, $packageSettings);
        if (empty($packages)) {
            return null;
        }

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
        ]);
    }
}
