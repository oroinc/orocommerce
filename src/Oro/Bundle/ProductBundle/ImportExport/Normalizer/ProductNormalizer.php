<?php

namespace Oro\Bundle\ProductBundle\ImportExport\Normalizer;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\ConfigurableEntityNormalizer;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\ImportExport\Event\ProductNormalizerEvent;

class ProductNormalizer extends ConfigurableEntityNormalizer
{
    /**
     * @var string
     */
    protected $productClass;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param string $productClass
     */
    public function setProductClass($productClass)
    {
        $this->productClass = $productClass;
    }

    /**
     * @param Product $object
     *
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $data = parent::normalize($object, $format, $context);

        if (array_key_exists('unitPrecisions', $data) && is_array($data['unitPrecisions'])) {
            foreach ($data['unitPrecisions'] as $v) {
                if ($v['unit']['code'] !== $object->getPrimaryUnitPrecision()->getUnit()->getCode()) {
                    $data['additionalUnitPrecisions'][] = $v;
                }
            }
            unset($data['unitPrecisions']);
        }

        if ($this->eventDispatcher) {
            $event = new ProductNormalizerEvent($object, $data, $context);
            $this->eventDispatcher->dispatch(ProductNormalizerEvent::NORMALIZE, $event);
            $data = $event->getPlainData();
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        if (array_key_exists('additionalUnitPrecisions', $data)) {
            $data['unitPrecisions'] = $data['additionalUnitPrecisions'];
            unset($data['additionalUnitPrecisions']);
            foreach ($data['unitPrecisions'] as &$unitPrecisionData) {
                $unitPrecisionData['sell'] = !empty($unitPrecisionData['sell']);
            }
            unset($unitPrecisionData);
        }

        if (array_key_exists('hasVariants', $data) && $data['hasVariants'] == null) {
            $data['hasVariants'] = false;
        }

        /**
         * @var Product $object
         */
        $object = parent::denormalize($data, $class, $format, $context);

        if ($this->eventDispatcher) {
            $event = new ProductNormalizerEvent($object, $data, $context);
            $this->eventDispatcher->dispatch(ProductNormalizerEvent::DENORMALIZE, $event);
            $object = $event->getProduct();
        }

        return $object;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null, array $context = [])
    {
        return is_a($data, $this->productClass);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null, array $context = [])
    {
        return is_a($type, $this->productClass, true);
    }
}
