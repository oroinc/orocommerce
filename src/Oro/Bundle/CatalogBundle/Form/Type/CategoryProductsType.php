<?php

namespace Oro\Bundle\CatalogBundle\Form\Type;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\CategorySortOrderGridType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Represents form for manual management of included/excluded products of the category.
 */
class CategoryProductsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'appendProducts',
                EntityIdentifierType::class,
                [
                    'class' => Product::class,
                    'mapped' => false,
                    'multiple' => true,
                    'invalid_message' => 'oro.catalog.category.products.append_products_invalid',
                ]
            )
            ->add(
                'removeProducts',
                EntityIdentifierType::class,
                [
                    'class' => Product::class,
                    'mapped' => false,
                    'multiple' => true,
                    'invalid_message' => 'oro.catalog.category.products.remove_products_invalid',
                ]
            )
            ->add(
                'sortOrder',
                CategorySortOrderGridType::class,
                [
                    'mapped' => false,
                    'invalid_message' => 'oro.catalog.category.products.sort_order_invalid',
                ]
            );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Category::class,
            'csrf_protection' => false,
        ]);
    }
}
