<?php

namespace OroB2B\Bundle\WarehouseBundle\ImportExport\Normalizer;

use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\NormalizerInterface;

use OroB2B\Bundle\WarehouseBundle\Entity\WarehouseInventoryLevel;

class InventoryLevelNormalizer implements NormalizerInterface
{
    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null, array $context = array())
    {
        return $data instanceof WarehouseInventoryLevel;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = array())
    {
        /** @var WarehouseInventoryLevel $object */
        $product = $object->getProduct();
        $unitPrecision = $object->getProductUnitPrecision();
        $result = [
            'product' => [
                'sku' => $product->getSku(),
                'defaultName' => $product->getDefaultName() ? $product->getDefaultName()->getString() : null,
                'inventoryStatus' => ($product->getInventoryStatus()) ? $product->getInventoryStatus()->getName() : null
            ],
            'warehouse' => [
                'name' => $object->getWarehouse()->getName()
            ],
            'quantity' => $object->getQuantity(),
            'productUnitPrecision' => [
                'unit' => [
                    'code' => $unitPrecision
                        ? ($unitPrecision->getUnit() ? $unitPrecision->getUnit()->getCode() : null)
                        : null
                ]
            ]

        ];

        return $result;
    }
}
