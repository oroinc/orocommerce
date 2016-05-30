<?php

namespace OroB2B\Bundle\ProductBundle\ImportExport\Normalizer;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\ConfigurableEntityNormalizer;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\ImportExport\Event\ProductNormalizerEvent;

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
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $data = parent::normalize($object, $format, $context);
        $primaryUnitCode = null;
        if ($object->getPrimaryUnitPrecision()) {
            $primaryUnitCode = $object->getPrimaryUnitPrecision()->getUnit()->getCode();
        }

        if (array_key_exists('unitPrecisions', $data)) {
            foreach ($data['unitPrecisions'] as $v) {
                if ($v['unit']['code'] == $primaryUnitCode) {
                    $data['primaryUnitPrecision'] = $v;
                } else {
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
        }
        $primaryCode = null;
        if (array_key_exists('primaryUnitPrecision', $data)) {
            $data['unitPrecisions'][] = $data['primaryUnitPrecision'];
            if (array_key_exists('unit', $data['primaryUnitPrecision']) &&
                array_key_exists('code', $data['primaryUnitPrecision']['unit'])) {
                $primaryCode = $data['primaryUnitPrecision']['unit']['code'];
            }
            unset($data['primaryUnitPrecision']);
        }

        /** @var Product $object */
        $object = parent::denormalize($data, $class, $format, $context);
        $primaryPrecision = $object->getUnitPrecision($primaryCode);
        if ($primaryPrecision) {
            $object->setPrimaryUnitPrecision($primaryPrecision);
        }

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
