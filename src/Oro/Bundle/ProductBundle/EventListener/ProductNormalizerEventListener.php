<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\ProductBundle\ImportExport\Event\ProductNormalizerEvent;

class ProductNormalizerEventListener
{
    const DELIMITER = ',';
    const VARIANT_FIELDS_KEY = 'variantFields';

    /**
     * @param ProductNormalizerEvent $event
     */
    public function onNormalize(ProductNormalizerEvent $event)
    {
        $context = $event->getContext();
        if (!$this->isApplicable($context)) {
            return;
        }

        $data = $event->getPlainData();
        if (array_key_exists(self::VARIANT_FIELDS_KEY, $data) && is_array($data[self::VARIANT_FIELDS_KEY])) {
            $data[self::VARIANT_FIELDS_KEY] = implode(self::DELIMITER, $data[self::VARIANT_FIELDS_KEY]);
            $event->setPlainData($data);
        }
    }

    /**
     * @param ProductNormalizerEvent $event
     */
    public function onDenormalize(ProductNormalizerEvent $event)
    {
        $context = $event->getContext();
        if (!$this->isApplicable($context)) {
            return;
        }

        $data = $event->getPlainData();
        $object = $event->getProduct();
        $variantFields = [];
        if (array_key_exists(self::VARIANT_FIELDS_KEY, $data) && is_string($data[self::VARIANT_FIELDS_KEY])) {
            $variantFields = explode(self::DELIMITER, $data[self::VARIANT_FIELDS_KEY]);
            $variantFields = array_map('trim', $variantFields);
            $variantFields = array_filter($variantFields);
            $variantFields = array_values($variantFields);
        }

        $object->setVariantFields($variantFields);
    }

    /**
     * No need to normalize related products
     *
     * @param array $context
     * @return bool
     */
    protected function isApplicable(array $context)
    {
        return !array_key_exists('fieldName', $context);
    }
}
