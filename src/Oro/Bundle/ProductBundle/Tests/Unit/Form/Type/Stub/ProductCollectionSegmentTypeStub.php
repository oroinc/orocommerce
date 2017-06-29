<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\ProductCollectionSegmentType;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductCollectionSegmentTypeStub extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', TextType::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return ProductCollectionSegmentType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Segment::class,
            'results_grid' => 'product-collection-grid',
            'included_products_grid' => 'product-collection-included-products-grid',
            'excluded_products_grid' => 'product-collection-excluded-products-grid',
            'label' => false,
            'segment_entity' => Product::class,
            'segment_columns' => ['id', 'sku'],
            'segment_name_template' => 'Product Collection %s',
            'constraints' => [],
            'error_bubbling' => false,
            'scope_value' => ProductCollectionSegmentType::DEFAULT_SCOPE_VALUE
        ]);
    }
}
