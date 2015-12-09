<?php

namespace OroB2B\Bundle\ProductBundle\ImportExport\Normalizer;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\ConfigurableEntityNormalizer;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\ImportExport\Event\ProductNormalizerEvent;

class ProductNormalizer extends ConfigurableEntityNormalizer
{
    /** @var string */
    protected $productClass;

    /** @var EventDispatcherInterface */
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
        /** @var Product $object */
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
