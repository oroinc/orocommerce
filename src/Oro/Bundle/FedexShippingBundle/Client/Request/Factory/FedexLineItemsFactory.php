<?php

namespace Oro\Bundle\FedexShippingBundle\Client\Request\Factory;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\FedexShippingBundle\Client\Request\FedexRequest;
use Oro\Bundle\FedexShippingBundle\Client\Request\FedexRequestInterface;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\ShippingLineItemCollectionInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItemInterface;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use Oro\Bundle\ShippingBundle\Provider\MeasureUnitConversion;

class FedexLineItemsFactory implements FedexRequestFactoryInterface
{
    const MAX_PACKAGE_WEIGHT_KGS = 70;
    const MAX_PACKAGE_WEIGHT_LBS = 150;

    const SHIPPING_WEIGHT_KG = 'kg';
    const SHIPPING_WEIGHT_LBS = 'lbs';

    const SHIPPING_DIMENSION_CM = 'cm';
    const SHIPPING_DIMENSION_INCH = 'inch';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var MeasureUnitConversion
     */
    protected $measureUnitConverter;

    /**
     * @param ManagerRegistry       $registry
     * @param MeasureUnitConversion $measureUnitConverter
     */
    public function __construct(ManagerRegistry $registry, MeasureUnitConversion $measureUnitConverter)
    {
        $this->registry = $registry;
        $this->measureUnitConverter = $measureUnitConverter;
    }

    /**
     * {@inheritDoc}
     */
    public function create(
        FedexIntegrationSettings $settings,
        ShippingContextInterface $context
    ): FedexRequestInterface {
        $packages = [];
        $lineItems = $this->getLineItemsWithShippingOptions($context->getLineItems(), $settings);

        $maxWeight = $this->getMaxWeightValue($settings);
        $weight = 0;
        $height = 0;
        $width = 0;
        $length = 0;
        foreach ($lineItems as $item) {
            $itemWeight = $item->getWeight()->getValue();
            if ($itemWeight > $maxWeight) {
                return new FedexRequest();
            }

            for ($i = 0; $i < $item->getQuantity(); $i++) {
                if ($weight + $itemWeight > $maxWeight) {
                    $packages[] = [
                        'Weight' => [
                            'Value' => $weight,
                            'Units' => $settings->getUnitOfWeight(),
                        ],
                        'Dimensions' => [
                            'Length' => $length,
                            'Width' => $width,
                            'Height' => $height,
                            'Units' => $this->getFedexDimensionCode($settings),
                        ],
                    ];

                    $weight = 0;
                    $height = 0;
                    $width = 0;
                    $length = 0;
                }

                $weight += $itemWeight;
                $height += $item->getDimensions()->getValue()->getHeight();
                $width += $item->getDimensions()->getValue()->getWidth();
                $length += $item->getDimensions()->getValue()->getLength();
            }
        }

        if ($weight > 0) {
            $packages[] = [
                'Weight' => [
                    'Value' => $weight,
                    'Units' => $settings->getUnitOfWeight(),
                ],
                'Dimensions' => [
                    'Length' => $length,
                    'Width' => $width,
                    'Height' => $height,
                    'Units' => $this->getFedexDimensionCode($settings),
                ],
            ];
        }

        return new FedexRequest($packages);
    }

    /**
     * @param ShippingLineItemCollectionInterface $lineItems
     * @param FedexIntegrationSettings            $settings
     *
     * @return ShippingLineItemInterface[]
     */
    private function getLineItemsWithShippingOptions(
        ShippingLineItemCollectionInterface $lineItems,
        FedexIntegrationSettings $settings
    ): array {
        $productsInfo = $this->getProductAndUnitInfo($lineItems);

        $shippingOptions = $this->getConvertedProductShippingOptions(
            $settings,
            array_column($productsInfo, 'product'),
            array_column($productsInfo, 'productUnit')
        );

        $result = [];
        /** @var ShippingLineItemInterface $item */
        foreach ($lineItems as $item) {
            $productId = $item->getProduct()->getId();
            $productUnitCode = $item->getProductUnitCode();
            if (!isset($shippingOptions[$productId][$productUnitCode])) {
                return [];
            }

            /** @var ProductShippingOptions $shippingOption */
            $shippingOption = $shippingOptions[$productId][$productUnitCode];

            $result[] = new ShippingLineItem([
                ShippingLineItem::FIELD_WEIGHT => $shippingOption->getWeight(),
                ShippingLineItem::FIELD_DIMENSIONS => $shippingOption->getDimensions(),
                ShippingLineItem::FIELD_QUANTITY => $item->getQuantity(),
            ]);
        }

        return $result;
    }

    /**
     * @param ShippingLineItemCollectionInterface $lineItems
     *
     * @return array
     */
    private function getProductAndUnitInfo(ShippingLineItemCollectionInterface $lineItems): array
    {
        $productsInfo = [];

        /** @var ShippingLineItemInterface $item */
        foreach ($lineItems as $item) {
            if (null === $item->getProduct()) {
                return [];
            }

            $productsInfo[] = [
                'product' => $item->getProduct(),
                'productUnit' => $item->getProductUnit(),
            ];
        }

        return $productsInfo;
    }

    /**
     * @param FedexIntegrationSettings $settings
     * @param Product[]                $products
     * @param ProductUnit[]            $productUnits
     *
     * @return array
     */
    private function getConvertedProductShippingOptions(
        FedexIntegrationSettings $settings,
        array $products,
        array $productUnits
    ): array {
        $options = $this->getProductShippingOptions($products, $productUnits);

        $result = [];
        foreach ($options as $option) {
            if (!$option->getDimensions() || !$option->getWeight()) {
                return [];
            }

            $dimension = $this->measureUnitConverter->convertDimensions(
                $option->getDimensions(),
                $this->getShippingDimensionCode($settings)
            );
            $weight = $this->measureUnitConverter->convertWeight(
                $option->getWeight(),
                $this->getShippingWeightCode($settings)
            );

            if (!$dimension || !$weight) {
                return [];
            }

            $option->setDimensions($dimension);
            $option->setWeight($weight);

            $result[$option->getProduct()->getId()][$option->getProductUnitCode()] = $option;
        }

        return $result;
    }

    /**
     * @param Product[]     $products
     * @param ProductUnit[] $productUnits
     *
     * @return ProductShippingOptions[]
     */
    private function getProductShippingOptions(array $products, array $productUnits): array
    {
        try {
            return $this->registry
                ->getManagerForClass('OroShippingBundle:ProductShippingOptions')
                ->getRepository('OroShippingBundle:ProductShippingOptions')
                ->findBy([
                    'product' => $products,
                    'productUnit' => $productUnits,
                ]);
        } catch (\UnexpectedValueException $e) {
            return [];
        }
    }

    /**
     * @param FedexIntegrationSettings $settings
     *
     * @return string
     */
    private function getShippingDimensionCode(FedexIntegrationSettings $settings): string
    {
        if ($settings->getUnitOfWeight() === FedexIntegrationSettings::UNIT_OF_WEIGHT_LB) {
            return self::SHIPPING_DIMENSION_INCH;
        }

        return self::SHIPPING_DIMENSION_CM;
    }

    /**
     * @param FedexIntegrationSettings $settings
     *
     * @return string
     */
    private function getShippingWeightCode(FedexIntegrationSettings $settings): string
    {
        if ($settings->getUnitOfWeight() === FedexIntegrationSettings::UNIT_OF_WEIGHT_LB) {
            return self::SHIPPING_WEIGHT_LBS;
        }

        return self::SHIPPING_WEIGHT_KG;
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
     * @return string
     */
    private function getFedexDimensionCode(FedexIntegrationSettings $settings): string
    {
        if ($settings->getUnitOfWeight() === FedexIntegrationSettings::UNIT_OF_WEIGHT_LB) {
            return FedexIntegrationSettings::DIMENSION_IN;
        }

        return FedexIntegrationSettings::DIMENSION_CM;
    }
}
