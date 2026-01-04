<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Oro\Bundle\ProductBundle\ContentVariantType\ProductPageContentVariantType;
use Oro\Component\WebCatalog\Form\PageVariantType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Form type which represent Product Page as a content node variant.
 */
class ProductPageVariantType extends AbstractType
{
    public const NAME = 'oro_product_page_variant';

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'productPageProduct',
                ProductSelectType::class,
                [
                    'label' => 'oro.product.entity_label',
                    'required' => true,
                    'constraints' => [new NotBlank()],
                    // Enable configurable products for select
                    'autocomplete_alias' => 'oro_all_product_visibility_limited',
                    'grid_name' => 'all-products-select-grid'
                ]
            );
    }

    #[\Override]
    public function getParent(): ?string
    {
        return PageVariantType::class;
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'content_variant_type' => ProductPageContentVariantType::TYPE,
        ]);
    }
}
