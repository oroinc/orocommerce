<?php

namespace Oro\Bundle\FedexShippingBundle\Modifier;

use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\Transformer\FedexToShippingUnitTransformerInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Builder\Factory\ShippingLineItemBuilderFactoryInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Factory\ShippingLineItemCollectionFactoryInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\ShippingLineItemCollectionInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItemInterface;
use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Bundle\ShippingBundle\Model\Weight;
use Oro\Bundle\ShippingBundle\Provider\MeasureUnitConversion;

class ConvertToFedexUnitsShippingLineItemCollectionModifier implements
    ShippingLineItemCollectionBySettingsModifierInterface
{
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
     * @var ShippingLineItemCollectionFactoryInterface
     */
    private $collectionFactory;

    /**
     * @var ShippingLineItemBuilderFactoryInterface
     */
    private $lineItemBuilderFactory;

    public function __construct(
        MeasureUnitConversion $measureUnitConverter,
        FedexToShippingUnitTransformerInterface $weightUnitTransformer,
        FedexToShippingUnitTransformerInterface $dimensionsUnitTransformer,
        ShippingLineItemCollectionFactoryInterface $collectionFactory,
        ShippingLineItemBuilderFactoryInterface $lineItemBuilderFactory
    ) {
        $this->measureUnitConverter = $measureUnitConverter;
        $this->weightUnitTransformer = $weightUnitTransformer;
        $this->dimensionsUnitTransformer = $dimensionsUnitTransformer;
        $this->collectionFactory = $collectionFactory;
        $this->lineItemBuilderFactory = $lineItemBuilderFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function modify(
        ShippingLineItemCollectionInterface $lineItems,
        FedexIntegrationSettings $settings
    ): ShippingLineItemCollectionInterface {
        $newLineItems = [];
        /** @var ShippingLineItemInterface $item */
        foreach ($lineItems as $item) {
            $dimensions = null;
            if ($item->getDimensions()) {
                $dimensions = $this->measureUnitConverter->convertDimensions(
                    $item->getDimensions(),
                    $this->dimensionsUnitTransformer->transform($settings->getDimensionsUnit())
                );
            }

            $weight = null;
            if ($item->getWeight()) {
                $weight = $this->measureUnitConverter->convertWeight(
                    $item->getWeight(),
                    $this->weightUnitTransformer->transform($settings->getUnitOfWeight())
                );
            }

            $newLineItems[] = $this->createLineItemWithConvertedUnits($item, $dimensions, $weight);
        }

        return $this->collectionFactory->createShippingLineItemCollection($newLineItems);
    }

    private function createLineItemWithConvertedUnits(
        ShippingLineItemInterface $lineItem,
        Dimensions $dimensions = null,
        Weight $weight = null
    ): ShippingLineItemInterface {
        $builder = $this->lineItemBuilderFactory->createBuilder(
            $lineItem->getProductUnit(),
            $lineItem->getProductUnitCode(),
            $lineItem->getQuantity(),
            $lineItem->getProductHolder()
        );
        if ($lineItem->getPrice()) {
            $builder->setPrice($lineItem->getPrice());
        }
        if ($lineItem->getProduct()) {
            $builder->setProduct($lineItem->getProduct());
            $builder->setProductSku($lineItem->getProductSku());
        }

        if ($dimensions) {
            $builder->setDimensions($dimensions);
        }
        if ($weight) {
            $builder->setWeight($weight);
        }

        return $builder->getResult();
    }
}
