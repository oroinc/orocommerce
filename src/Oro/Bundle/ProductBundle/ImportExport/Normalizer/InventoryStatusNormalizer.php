<?php

namespace Oro\Bundle\ProductBundle\ImportExport\Normalizer;

use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;

/**
 * Normalizes product inventory status.
 */
class InventoryStatusNormalizer implements ContextAwareNormalizerInterface
{
    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        $processors = [
            'oro_product.inventory_status_only',
            'oro_product.inventory_status_only_template'
        ];

        return isset($context['processorAlias'])
                && in_array($context['processorAlias'], $processors, true)
                && $data instanceof Product;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, string $format = null, array $context = [])
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
