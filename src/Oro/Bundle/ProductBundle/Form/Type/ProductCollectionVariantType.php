<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\CheckboxType;
use Oro\Bundle\ProductBundle\ContentVariantType\ProductCollectionContentVariantType;
use Oro\Component\WebCatalog\Form\PageVariantType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for manager product collection variant type
 */
class ProductCollectionVariantType extends AbstractType
{
    public const NAME = 'oro_product_collection_variant';
    public const PRODUCT_COLLECTION_SEGMENT = 'productCollectionSegment';

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                self::PRODUCT_COLLECTION_SEGMENT,
                ProductCollectionSegmentType::class,
                [
                    'add_name_field' => true,
                    'add_sort_order' => true,
                    'results_grid' => 'product-collection-content-variant-grid',
                    'scope_value' => $builder->getName()
                ]
            )
            ->add(
                'overrideVariantConfiguration',
                CheckboxType::class,
                [
                    'label' => 'oro.product.override_variant_configuration.label',
                    'tooltip' => 'oro.product.override_variant_configuration.tooltip',
                ]
            );
    }

    #[\Override]
    public function getParent(): ?string
    {
        return PageVariantType::class;
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'content_variant_type' => ProductCollectionContentVariantType::TYPE
        ]);
    }
}
