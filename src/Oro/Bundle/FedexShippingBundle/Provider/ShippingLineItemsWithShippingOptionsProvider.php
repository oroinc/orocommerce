<?php


namespace Oro\Bundle\FedexShippingBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\Transformer\FedexToShippingUnitTransformerInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\ShippingLineItemCollectionInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItemInterface;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use Oro\Bundle\ShippingBundle\Provider\MeasureUnitConversion;

class ShippingLineItemsWithShippingOptionsProvider implements ShippingLineItemsByContextAndSettingsProviderInterface
{
    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var MeasureUnitConversion
     */
    private $measureUnitConverter;

    /**
     * @var FedexToShippingUnitTransformerInterface
     */
    private $weightUnitTransformer;

    /**
     * @var FedexToShippingUnitTransformerInterface
     */
    private $dimensionsUnitTransformer;

    /**
     * @param ManagerRegistry                         $registry
     * @param MeasureUnitConversion                   $measureUnitConverter
     * @param FedexToShippingUnitTransformerInterface $weightUnitTransformer
     * @param FedexToShippingUnitTransformerInterface $dimensionsUnitTransformer
     */
    public function __construct(
        ManagerRegistry $registry,
        MeasureUnitConversion $measureUnitConverter,
        FedexToShippingUnitTransformerInterface $weightUnitTransformer,
        FedexToShippingUnitTransformerInterface $dimensionsUnitTransformer
    ) {
        $this->registry = $registry;
        $this->measureUnitConverter = $measureUnitConverter;
        $this->weightUnitTransformer = $weightUnitTransformer;
        $this->dimensionsUnitTransformer = $dimensionsUnitTransformer;
    }

    /**
     * {@inheritDoc}
     */
    public function get(
        FedexIntegrationSettings $settings,
        ShippingContextInterface $context
    ): array {
        $lineItems = $context->getLineItems();
        $productsInfo = $this->getProductAndUnitInfo($lineItems);
        if (empty($productsInfo)) {
            return [];
        }

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
                $this->dimensionsUnitTransformer->transform($settings->getDimensionsUnit())
            );
            $weight = $this->measureUnitConverter->convertWeight(
                $option->getWeight(),
                $this->weightUnitTransformer->transform($settings->getUnitOfWeight())
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
}
