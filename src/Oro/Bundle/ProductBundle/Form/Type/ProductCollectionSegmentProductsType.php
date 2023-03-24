<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Represents form for manual management of included/excluded products of the product collection segment.
 */
class ProductCollectionSegmentProductsType extends AbstractType
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
                    'invalid_message' => 'oro.product.product_collection.append_products_invalid',
                ]
            )
            ->add(
                'removeProducts',
                EntityIdentifierType::class,
                [
                    'class' => Product::class,
                    'mapped' => false,
                    'multiple' => true,
                    'invalid_message' => 'oro.product.product_collection.remove_products_invalid',
                ]
            )
            ->add(
                'sortOrder',
                CollectionSortOrderGridType::class,
                [
                    'mapped' => false,
                    'segment' => $options['segment'],
                    'invalid_message' => 'oro.product.product_collection.sort_order_invalid',
                ]
            );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'segment' => null,
        ]);

        $resolver->setAllowedTypes('segment', [Segment::class, 'null']);
    }
}
