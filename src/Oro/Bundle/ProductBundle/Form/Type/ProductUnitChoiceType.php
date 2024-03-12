<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatterInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for selecting a product unit.
 */
class ProductUnitChoiceType extends AbstractType
{
    private UnitLabelFormatterInterface $productUnitFormatter;

    public function __construct(UnitLabelFormatterInterface $productUnitLabelFormatter)
    {
        $this->productUnitFormatter = $productUnitLabelFormatter;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => ProductUnit::class,
        ]);

        $resolver
            ->define('compact')
            ->default(false)
            ->allowedTypes('bool')
            ->info('Switches product unit label format between "short" (true) and "full" (false)');

        $resolver
            ->define('product')
            ->default(null)
            ->allowedTypes(Product::class, 'null')
            ->info('Restricts the choices list by the product units of the specified product');

        $resolver
            ->define('sell')
            ->default(null)
            ->allowedTypes('bool', 'null')
            ->info('Restricts the choices list by the "sell" flag of product unit precisions of the specified product');

        $resolver->setDefault('choice_label', function (Options $options) {
            return fn (?ProductUnit $unit) => $unit
                ? $this->productUnitFormatter->format($unit->getCode(), $options['compact'])
                : $unit->getCode();
        });

        $resolver->setDefault('choices', function (Options $options, $previousValue) {
            if ($options['product'] === null) {
                return $previousValue;
            }

            /** @var Product $product */
            $product = $options['product'];
            $choices = $product->getAvailableUnits();

            if ($options['sell'] !== null) {
                // Filters choices according to the flag "sell".
                $choices = array_filter(
                    $choices,
                    static function (ProductUnit $unit) use ($product, $options) {
                        return $product->getUnitPrecision($unit->getCode())?->isSell() === $options['sell'];
                    }
                );
            }

            $primaryProductUnit = $product->getPrimaryUnitPrecision()?->getUnit();

            // Moves primary unit to the top of the choices array.
            foreach ($choices as $key => $productUnit) {
                if ($productUnit === $primaryProductUnit) {
                    unset($choices[$key]);
                    array_unshift($choices, $primaryProductUnit);
                    break;
                }
            }

            return array_values($choices);
        });
    }

    public function getParent(): ?string
    {
        return EntityType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'oro_product_unit_select';
    }
}
