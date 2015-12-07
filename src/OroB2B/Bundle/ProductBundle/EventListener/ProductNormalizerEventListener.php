<?php

namespace OroB2B\Bundle\ProductBundle\EventListener;

use Oro\Bundle\ImportExportBundle\Field\FieldHelper;

use OroB2B\Bundle\ProductBundle\ImportExport\Event\ProductNormalizerEvent;

class ProductNormalizerEventListener
{
    const DELIMITER = ',';
    const VARIANT_FIELDS_PROPERTY = 'variantFields';

    /**
     * @var FieldHelper
     */
    protected $fieldHelper;

    /**
     * @param FieldHelper $fieldHelper
     */
    public function __construct(FieldHelper $fieldHelper)
    {
        $this->fieldHelper = $fieldHelper;
    }

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
        if (array_key_exists(self::VARIANT_FIELDS_PROPERTY, $data) && is_array($data[self::VARIANT_FIELDS_PROPERTY])) {
            $data[self::VARIANT_FIELDS_PROPERTY] = implode(self::DELIMITER, $data[self::VARIANT_FIELDS_PROPERTY]);
            $event->setPlainData($data);
        }
    }

    /**
     * @param ProductNormalizerEvent $event
     */
    public function onDeNormalize(ProductNormalizerEvent $event)
    {
        $context = $event->getContext();
        if (!$this->isApplicable($context)) {
            return;
        }

        $data = $event->getPlainData();
        $object = $event->getProduct();
        $variantFields = [];
        if (array_key_exists(self::VARIANT_FIELDS_PROPERTY, $data) && is_array($data[self::VARIANT_FIELDS_PROPERTY])) {
            $variantFields = explode(self::DELIMITER, $data[self::VARIANT_FIELDS_PROPERTY]);
        }

        $this->fieldHelper->setObjectValue($object, self::VARIANT_FIELDS_PROPERTY, $variantFields);
    }

    /**
     * No need to normalize related products
     *
     * @param array $context
     * @return bool
     */
    protected function isApplicable(array $context)
    {
        return isset($context['mode'], $context['fieldName']);
    }
}
