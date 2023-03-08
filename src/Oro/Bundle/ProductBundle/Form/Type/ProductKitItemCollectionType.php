<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Represents a form type for {@see Product::$kitItems} - collection of {@see ProductKitItem}.
 */
class ProductKitItemCollectionType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'add_label' => 'oro.product.productkititem.form.kit_item_collection.add',
            'entry_type' => ProductKitItemType::class,
            'entry_options' => [
                'required' => true,
            ],
        ]);
    }

    public function getParent(): string
    {
        return CollectionType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'oro_product_kit_items_collection';
    }
}
