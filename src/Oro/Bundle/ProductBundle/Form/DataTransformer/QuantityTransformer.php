<?php

namespace Oro\Bundle\ProductBundle\Form\DataTransformer;

use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * Specific transformer for product unit quantity. Helps to parse float in the correct localization.
 * We could not reuse \Symfony\Component\Form\Extension\Core\DataTransformer\NumberToLocalizedStringTransformer
 * because it could not work normally with grouping symbols in 'es' locale
 */
class QuantityTransformer implements DataTransformerInterface
{
    /**
     * @var NumberFormatter
     */
    private $numberFormatter;

    /**
     * @var bool
     */
    private $skipTransformation;

    public function __construct(NumberFormatter $formatter, bool $skipTransformation = false)
    {
        $this->numberFormatter = $formatter;
        $this->skipTransformation = $skipTransformation;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        if ($this->skipTransformation) {
            return $value;
        }

        $formattedValue = $this->numberFormatter->formatDecimal(
            $value,
            [
                \NumberFormatter::GROUPING_USED => false
            ]
        );

        return $formattedValue;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        if ($value === '') {
            return null;
        }

        $parsedValue = $this->numberFormatter->parseFormattedDecimal($value);
        if ($parsedValue === false) {
            throw new TransformationFailedException(
                sprintf('Quantity %s is not a valid decimal number', $value)
            );
        }

        return $parsedValue;
    }
}
