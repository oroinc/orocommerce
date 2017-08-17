<?php

namespace Oro\Bundle\FedexShippingBundle\Client\Request\Factory;

use Oro\Bundle\FedexShippingBundle\Client\Request\FedexRequest;
use Oro\Bundle\FedexShippingBundle\Client\Request\FedexRequestInterface;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\Provider\ShippingLineItemsByContextAndSettingsProviderInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;

class FedexLineItemsFactory implements FedexRequestByContextAndSettingsFactoryInterface
{
    const MAX_PACKAGE_WEIGHT_KGS = 70;
    const MAX_PACKAGE_WEIGHT_LBS = 150;

    const MAX_PACKAGE_LENGTH_INCH = 119;
    const MAX_PACKAGE_LENGTH_CM = 302.26;

    const MAX_PACKAGE_GIRTH_INCH = 165;
    const MAX_PACKAGE_GIRTH_CM = 419.1;

    /**
     * @var ShippingLineItemsByContextAndSettingsProviderInterface
     */
    private $lineItemsProvider;

    /**
     * @param ShippingLineItemsByContextAndSettingsProviderInterface $lineItemsProvider
     */
    public function __construct(ShippingLineItemsByContextAndSettingsProviderInterface $lineItemsProvider)
    {
        $this->lineItemsProvider = $lineItemsProvider;
    }

    /**
     * {@inheritDoc}
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function create(
        FedexIntegrationSettings $settings,
        ShippingContextInterface $context
    ): FedexRequestInterface {
        $packages = [];
        $lineItems = $this->lineItemsProvider->get($settings, $context);

        $maxWeight = $this->getMaxWeightValue($settings);
        $maxLength = $this->getMaxLengthValue($settings);
        $maxGirth = $this->getMaxGirthValue($settings);
        $weight = 0;
        $height = 0;
        $width = 0;
        $length = 0;
        foreach ($lineItems as $item) {
            $itemWeight = $item->getWeight()->getValue();
            $itemLength = $item->getDimensions()->getValue()->getLength();
            $itemGirth = $this->getGirth(
                $itemLength,
                $item->getDimensions()->getValue()->getWidth(),
                $item->getDimensions()->getValue()->getHeight()
            );
            if ($itemWeight > $maxWeight || $itemLength > $maxLength || $itemGirth > $maxGirth) {
                return new FedexRequest();
            }

            for ($i = 0; $i < $item->getQuantity(); $i++) {
                if ($weight + $itemWeight > $maxWeight ||
                    $length + $itemLength > $maxLength ||
                    $this->getGirth($length, $width, $height) + $itemGirth > $maxGirth) {
                    $packages[] = $this->createPackage($settings, $weight, $length, $width, $height);

                    $weight = 0;
                    $height = 0;
                    $width = 0;
                    $length = 0;
                }

                $weight += $itemWeight;
                $height += $item->getDimensions()->getValue()->getHeight();
                $width += $item->getDimensions()->getValue()->getWidth();
                $length += $itemLength;
            }
        }

        if ($weight > 0) {
            $packages[] = $this->createPackage($settings, $weight, $length, $width, $height);
        }

        return new FedexRequest($packages);
    }

    /**
     * @param FedexIntegrationSettings $settings
     * @param float                    $weight
     * @param float                    $length
     * @param float                    $width
     * @param float                    $height
     *
     * @return array
     */
    private function createPackage(
        FedexIntegrationSettings $settings,
        float $weight,
        float $length,
        float $width,
        float $height
    ): array {
        return [
            'GroupPackageCount' => 1,
            'Weight' => [
                'Value' => $weight,
                'Units' => $settings->getUnitOfWeight(),
            ],
            'Dimensions' => [
                'Length' => $length,
                'Width' => $width,
                'Height' => $height,
                'Units' => $settings->getDimensionsUnit(),
            ],
        ];
    }

    /**
     * @param FedexIntegrationSettings $settings
     *
     * @return float
     */
    private function getMaxWeightValue(FedexIntegrationSettings $settings): float
    {
        if ($settings->getUnitOfWeight() === FedexIntegrationSettings::UNIT_OF_WEIGHT_LB) {
            return self::MAX_PACKAGE_WEIGHT_LBS;
        }

        return self::MAX_PACKAGE_WEIGHT_KGS;
    }

    /**
     * @param FedexIntegrationSettings $settings
     *
     * @return float
     */
    private function getMaxLengthValue(FedexIntegrationSettings $settings): float
    {
        if ($settings->getDimensionsUnit() === FedexIntegrationSettings::DIMENSION_CM) {
            return self::MAX_PACKAGE_LENGTH_CM;
        }

        return self::MAX_PACKAGE_LENGTH_INCH;
    }

    /**
     * @param FedexIntegrationSettings $settings
     *
     * @return float
     */
    private function getMaxGirthValue(FedexIntegrationSettings $settings): float
    {
        if ($settings->getDimensionsUnit() === FedexIntegrationSettings::DIMENSION_CM) {
            return self::MAX_PACKAGE_GIRTH_CM;
        }

        return self::MAX_PACKAGE_GIRTH_INCH;
    }

    /**
     * @param float $length
     * @param float $width
     * @param float $height
     *
     * @return float
     */
    private function getGirth(float $length, float $width, float $height): float
    {
        return $length + 2 * $width + 2 * $height;
    }
}
