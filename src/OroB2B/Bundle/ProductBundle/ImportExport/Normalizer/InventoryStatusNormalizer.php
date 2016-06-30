<?php

namespace OroB2B\Bundle\ProductBundle\ImportExport\Normalizer;

use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\NormalizerInterface;

use OroB2B\Bundle\ProductBundle\Entity\Product;

class InventoryStatusNormalizer implements NormalizerInterface
{
    const PRODUCT_INVENTORY_STATUS_ONLY_PROCESSOR = 'orob2b_product.export_inventory_status_only';
    const WAREHOUSE_INVENTORY_STATUS_ONLY_PROCESSOR = 'orob2b_product.inventory_status_only_export_template';
    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null, array $context = array())
    {
        return isset($context['processorAlias'])
                && ($context['processorAlias'] == self::PRODUCT_INVENTORY_STATUS_ONLY_PROCESSOR
                || $context['processorAlias'] == self::WAREHOUSE_INVENTORY_STATUS_ONLY_PROCESSOR)
                && $data instanceof Product;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = array())
    {
        return [
            'product' => [
                'sku' => $object->getSku(),
                'defaultName' => $object->getDefaultName() ? $object->getDefaultName()->getString() : null,
                'inventoryStatus' => ($object->getInventoryStatus()) ? $object->getInventoryStatus()->getName() : null
            ]
        ];
    }
}
