<?php

namespace Oro\Bundle\ProductBundle\ProductVariant\VariantFieldValueHandler;

use Oro\Bundle\ProductBundle\ProductVariant\Registry\ProductVariantFieldValueHandlerInterface;
use Symfony\Component\Translation\TranslatorInterface;

class BooleanVariantFieldValueHandler implements ProductVariantFieldValueHandlerInterface
{
    const TYPE = 'boolean';

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function getPossibleValues($fieldName)
    {
        return [
            0 => $this->translator->trans('oro.product.variant_fields.no.label'),
            1 => $this->translator->trans('oro.product.variant_fields.yes.label'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getScalarValue($value)
    {
        return (bool)$value;
    }

    /**
     * {@inheritdoc}
     */
    public function getHumanReadableValue($fieldName, $value)
    {
        $values = $this->getPossibleValues($fieldName);

        return $values[$value] ?? 'N/A';
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return self::TYPE;
    }
}
