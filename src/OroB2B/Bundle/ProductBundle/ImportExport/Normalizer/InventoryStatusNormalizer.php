<?php

namespace OroB2B\Bundle\ProductBundle\ImportExport\Normalizer;

use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\NormalizerInterface;

use OroB2B\Bundle\ProductBundle\Entity\Product;

class InventoryStatusNormalizer implements NormalizerInterface
{
    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null, array $context = array())
    {
        $processors = [
            'orob2b_product.inventory_status_only',
            'orob2b_product.inventory_status_only_template'
        ];

        return isset($context['processorAlias'])
                && in_array($context['processorAlias'], $processors)
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
