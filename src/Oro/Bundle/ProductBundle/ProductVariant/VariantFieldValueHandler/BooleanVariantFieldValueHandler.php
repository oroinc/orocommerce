<?php

namespace Oro\Bundle\ProductBundle\ProductVariant\VariantFieldValueHandler;

use Oro\Bundle\ProductBundle\ProductVariant\Registry\ProductVariantFieldValueHandlerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides easy way to work with the boolean fields of the Product entity.
 */
class BooleanVariantFieldValueHandler implements ProductVariantFieldValueHandlerInterface
{
    public const TYPE = 'boolean';

    protected TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function getPossibleValues(string $fieldName) : array
    {
        return [
            0 => $this->translator->trans('oro.product.variant_fields.no.label'),
            1 => $this->translator->trans('oro.product.variant_fields.yes.label'),
        ];
    }

    public function getScalarValue(mixed $value) : mixed
    {
        return (bool)$value;
    }

    public function getHumanReadableValue(string $fieldName, mixed $value) : mixed
    {
        $values = $this->getPossibleValues($fieldName);

        return array_key_exists((int) $value, $values) ? $values[(int) $value] : 'N/A';
    }

    public function getType() : string
    {
        return self::TYPE;
    }
}
