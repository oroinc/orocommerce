<?php

namespace Oro\Bundle\ProductBundle\ImportExport\Normalizer;

use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\ConfigurableEntityNormalizer;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\ImportExport\Event\ProductNormalizerEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * The import/export normalizer for Product entities.
 */
class ProductNormalizer extends ConfigurableEntityNormalizer
{
    private EventDispatcherInterface $eventDispatcher;

    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): void
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    #[\Override]
    public function normalize(
        mixed $object,
        ?string $format = null,
        array $context = []
    ): float|int|bool|\ArrayObject|array|string|null {
        $data = parent::normalize($object, $format, $context);

        if (array_key_exists('unitPrecisions', $data) && is_array($data['unitPrecisions'])) {
            foreach ($data['unitPrecisions'] as $v) {
                if ($v['unit']['code'] !== $object->getPrimaryUnitPrecision()?->getUnit()?->getCode()) {
                    $data['additionalUnitPrecisions'][] = $v;
                }
            }
            unset($data['unitPrecisions']);
        }

        $event = new ProductNormalizerEvent($object, $data, $context);
        $this->eventDispatcher->dispatch($event, ProductNormalizerEvent::NORMALIZE);

        return $event->getPlainData();
    }

    #[\Override]
    public function denormalize($data, string $type, ?string $format = null, array $context = []): mixed
    {
        if (array_key_exists('additionalUnitPrecisions', $data)) {
            $data['unitPrecisions'] = $data['additionalUnitPrecisions'];
            unset($data['additionalUnitPrecisions']);
            foreach ($data['unitPrecisions'] as &$unitPrecisionData) {
                $unitPrecisionData['sell'] = !empty($unitPrecisionData['sell']);
            }
            unset($unitPrecisionData);
        }

        /** @var Product $object */
        $object = parent::denormalize($data, $type, $format, $context);

        $event = new ProductNormalizerEvent($object, $data, $context);
        $this->eventDispatcher->dispatch($event, ProductNormalizerEvent::DENORMALIZE);

        return $event->getProduct();
    }

    #[\Override]
    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        return is_a($data, Product::class);
    }

    #[\Override]
    public function supportsDenormalization($data, string $type, ?string $format = null, array $context = []): bool
    {
        return is_a($type, Product::class, true);
    }
}
