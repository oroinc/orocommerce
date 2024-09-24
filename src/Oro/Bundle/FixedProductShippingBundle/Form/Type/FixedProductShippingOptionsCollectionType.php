<?php

namespace Oro\Bundle\FixedProductShippingBundle\Form\Type;

use Oro\Bundle\PricingBundle\Form\Type\ProductAttributePriceCollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Fixed product shipping options form type.
 */
class FixedProductShippingOptionsCollectionType extends AbstractType
{
    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'entry_type' => ProductAttributePriceCollectionType::class,
            'attr' => ['class' => 'fixed-product-shipping-cost'],
            'check_field_name' => null
        ]);
    }


    #[\Override]
    public function getParent(): string
    {
        return CollectionType::class;
    }
}
