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
        // Set quantity to null if not exporting real object
        if (!$object->getId() && 0 == $object->getQuantity()) {
            $object->setQuantity(null);
        }

        $product = $object->getProduct();
        $result = [
            'quantity' => $object->getQuantity()
        ];

        if ($product) {
            $result['product'] = [
                'sku' => $product->getSku(),
                'defaultName' => $product->getDefaultName() ? $product->getDefaultName()->getString() : null,
                'inventoryStatus' => ($product->getInventoryStatus()) ? $product->getInventoryStatus()->getName() : null
            ];
        }

        if ($object->getWarehouse()) {
            $result['warehouse'] = [
                'name' => $object->getWarehouse()->getName()
            ];
        }

        $result = array_merge($result, $this->getUnitPrecision($object));

        return $result;
    }

    /**
     * @param WarehouseInventoryLevel $object
     * @return array
     */
    protected function getUnitPrecision(WarehouseInventoryLevel $object)
    {
        $unitPrecision = $object->getProductUnitPrecision();
        if (!$unitPrecision) {
            return [];
        }

        return ['productUnitPrecision' => [
            'unit' => [
                'code' => $unitPrecision->getUnit() ? $unitPrecision->getUnit()->getCode() : null
            ]
        ]];
    }
}