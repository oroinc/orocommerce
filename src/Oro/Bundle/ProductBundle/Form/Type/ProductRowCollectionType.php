<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type representing a row in {@see QuickAddType}
 */
class ProductRowCollectionType extends AbstractType
{
    public const NAME = 'oro_product_row_collection';

    public function getParent(): ?string
    {
        return CollectionType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'entry_type' => ProductRowType::class,
                'required' => false,
                'handle_primary' => false,
                'row_count_add' => 5,
                'row_count_initial' => 8,
            ]
        );
    }

    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
