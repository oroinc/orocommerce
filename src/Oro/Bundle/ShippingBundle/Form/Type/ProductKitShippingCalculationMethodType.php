<?php

namespace Oro\Bundle\ShippingBundle\Form\Type;

use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for selecting product KitShippingCalculationMethod
 */
class ProductKitShippingCalculationMethodType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'choices' => [
                'oro.product.kit_shipping_calculation_method.choices.kit_shipping_all' =>
                    Product::KIT_SHIPPING_ALL,
                'oro.product.kit_shipping_calculation_method.choices.kit_shipping_product' =>
                    Product::KIT_SHIPPING_ONLY_PRODUCT,
                'oro.product.kit_shipping_calculation_method.choices.kit_shipping_items' =>
                    Product::KIT_SHIPPING_ONLY_ITEMS,
            ],
            'placeholder' => false,
        ]);
    }

    public function getParent()
    {
        return ChoiceType::class;
    }
}
