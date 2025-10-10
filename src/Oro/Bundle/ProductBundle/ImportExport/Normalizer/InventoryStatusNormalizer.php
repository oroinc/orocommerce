<?php

namespace Oro\Bundle\ProductBundle\ImportExport\Normalizer;

use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Normalizes product inventory status.
 */
class InventoryStatusNormalizer implements NormalizerInterface
{
    #[\Override]
    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        $processors = [
            'oro_product.inventory_status_only',
            'oro_product.inventory_status_only_template'
        ];

        return isset($context['processorAlias'])
                && in_array($context['processorAlias'], $processors, true)
                && $data instanceof Product;
    }

    #[\Override]
    public function normalize(
        mixed $object,
        ?string $format = null,
        array $context = []
    ): float|int|bool|\ArrayObject|array|string|null {
        return [
            'product' => [
                'sku' => $object->getSku(),
                'defaultName' => $object->getDefaultName() ? $object->getDefaultName()->getString() : null,
                'inventoryStatus' => ($object->getInventoryStatus()) ? $object->getInventoryStatus()->getName() : null
            ]
        ];
    }

    public function getSupportedTypes(?string $format): array
    {
        return [Product::class => true];
    }
}
