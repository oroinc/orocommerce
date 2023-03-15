<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Form\Type;

use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use Oro\Bundle\ProductBundle\Form\Type\QuantityType;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type that represents a shopping list line item of a product kit.
 */
class ProductKitLineItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('quantity', QuantityType::class)
            ->add('unit', ProductUnitSelectionType::class)
            ->add(
                'kitItemLineItems',
                CollectionType::class,
                [
                    'required' => false,
                    'allow_add' => false,
                    'allow_delete' => false,
                    'entry_type' => ProductKitItemLineItemType::class,
                ]
            );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => LineItem::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'oro_product_kit_line_item';
    }
}
