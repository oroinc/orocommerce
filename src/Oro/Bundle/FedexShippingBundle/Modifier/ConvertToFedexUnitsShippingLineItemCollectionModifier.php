<?php

namespace Oro\Bundle\FedexShippingBundle\Modifier;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\Transformer\FedexToShippingUnitTransformerInterface;
use Oro\Bundle\ShippingBundle\Provider\MeasureUnitConversion;

/**
 * Converts Shipping Line Item collection to Shipping Line Item collection with converted Units (Dimensions and Weight)
 */
class ConvertToFedexUnitsShippingLineItemCollectionModifier implements
    ShippingLineItemCollectionBySettingsModifierInterface
{
    private MeasureUnitConversion $measureUnitConverter;

    private FedexToShippingUnitTransformerInterface $weightUnitTransformer;

    private FedexToShippingUnitTransformerInterface $dimensionsUnitTransformer;

    public function __construct(
        MeasureUnitConversion $measureUnitConverter,
        FedexToShippingUnitTransformerInterface $weightUnitTransformer,
        FedexToShippingUnitTransformerInterface $dimensionsUnitTransformer
    ) {
        $this->measureUnitConverter = $measureUnitConverter;
        $this->weightUnitTransformer = $weightUnitTransformer;
        $this->dimensionsUnitTransformer = $dimensionsUnitTransformer;
    }

    #[\Override]
    public function modify(
        Collection $shippingLineItems,
        FedexIntegrationSettings $settings
    ): Collection {
        foreach ($shippingLineItems as $shippingLineItem) {
            $dimensions = null;
            if ($shippingLineItem->getDimensions()) {
                $dimensions = $this->measureUnitConverter->convertDimensions(
                    $shippingLineItem->getDimensions(),
                    $this->dimensionsUnitTransformer->transform($settings->getDimensionsUnit())
                );
            }
            $shippingLineItem->setDimensions($dimensions);

            $weight = null;
            if ($shippingLineItem->getWeight()) {
                $weight = $this->measureUnitConverter->convertWeight(
                    $shippingLineItem->getWeight(),
                    $this->weightUnitTransformer->transform($settings->getUnitOfWeight())
                );
            }
            $shippingLineItem->setWeight($weight);
        }

        return $shippingLineItems;
    }
}
