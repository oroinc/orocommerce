<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Oro\Bundle\FormBundle\Utils\FormUtils;
use Oro\Bundle\ProductBundle\ContentVariantType\ProductCollectionContentVariantType;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Form\Type\SegmentFilterBuilderType;
use Oro\Component\WebCatalog\Form\PageVariantType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Valid;

class ProductCollectionVariantType extends AbstractType
{
    const NAME = 'oro_product_collection_variant';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'productCollectionSegment',
            SegmentFilterBuilderType::NAME,
            [
                'label' => 'oro.product.content_variant.field.product_collection.label',
                'segment_entity' => Product::class,
                'segment_columns' => ['id', 'sku'],
                'segment_name_template' => 'Product Collection %s',
                'add_name_field' => true,
                'name_field_required' => false,
                'tooltip' => 'oro.product.content_variant.field.product_collection.tooltip',
                'required' => true,
                'constraints' => [new NotBlank(), new Valid()]
            ]
        );

        // Make segment name required for existing segments
        $builder->get('productCollectionSegment')->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                $segment = $event->getData();
                if ($segment instanceof Segment && $segment->getId()) {
                    FormUtils::replaceField(
                        $event->getForm()->getParent(),
                        'productCollectionSegment',
                        ['name_field_required' => true]
                    );
                }
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return PageVariantType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
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
            'content_variant_type' => ProductCollectionContentVariantType::TYPE,
            'results_grid' => 'product-collection-grid',
            'included_products_grid' => 'product-collection-included-products-grid',
            'excluded_products_grid' => 'product-collection-excluded-products-grid'
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['results_grid'] = $options['results_grid'];
        $view->vars['includedProductsGrid'] = $options['included_products_grid'];
        $view->vars['excludedProductsGrid'] = $options['excluded_products_grid'];
        $view->vars['segmentDefinitionFieldName'] = $view->children['productCollectionSegment']
            ->children['definition']->vars['full_name'];
        $view->vars['segmentDefinition'] = $view->children['productCollectionSegment']
            ->children['definition']->vars['value'];
    }
}
