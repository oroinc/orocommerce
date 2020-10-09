<?php

namespace Oro\Bundle\ShippingBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Allows to work correctly with common units of measurement in different localizations.
 */
class CommonUnitValueType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $separator = $this->getDecimalSeparator($options['html5']);
        $transformer = new CallbackTransformer(
            function ($value) use ($separator) {
                if ($value) {
                    return $this->format($value, $separator);
                }

                return $value;
            },
            function ($value) {
                return $value;
            }
        );

        $builder->addViewTransformer($transformer);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        // Since it is not possible to know the value before configuration, we assume that the accuracy will be any.
        $resolver->setDefault('scale', PHP_FLOAT_DIG);
        $resolver->setAllowedTypes('scale', 'int');
        $resolver->setAllowedValues('scale', function ($value) {
            return $value >= PHP_FLOAT_DIG;
        });
    }

    /**
     * @param string $value
     * @param string $separator
     *
     * @return string
     */
    public function format(string $value, string $separator): string
    {
        if (false !== strpos($value, $separator)) {
            [$whole, $fraction] = explode($separator, $value);
            $fraction = rtrim($fraction, 0);

            return $fraction
                ? sprintf('%s%s%s', $whole, $separator, $fraction)
                : $whole;
        }

        return $value;
    }

    /**
     * @param string $lang
     *
     * @return string
     */
    private function getDecimalSeparator(string $lang): string
    {
        // NumberFormatter::DECIMAL - In this case, do not use integer formatted, only float.
        $formatter = new \NumberFormatter($lang, \NumberFormatter::DECIMAL);

        return $formatter->getSymbol(\NumberFormatter::DECIMAL_SEPARATOR_SYMBOL);
    }


    /**
     * @return string
     */
    public function getParent(): string
    {
        return NumberType::class;
    }
}
