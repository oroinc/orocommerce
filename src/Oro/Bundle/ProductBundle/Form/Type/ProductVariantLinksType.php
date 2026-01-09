<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Oro\Bundle\ProductBundle\Form\DataTransformer\ProductVariantLinksDataTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for managing product variant links in configurable products.
 *
 * This form type provides fields for appending and removing product variants, using a data transformer
 * to handle the conversion between form data and the actual product variant link entities.
 */
class ProductVariantLinksType extends AbstractType
{
    public const NAME = 'oro_product_variant_links';

    /** @var ProductVariantLinksDataTransformer */
    protected $transformer;

    public function __construct(?ProductVariantLinksDataTransformer $transformer = null)
    {
        $this->transformer = $transformer ?: new ProductVariantLinksDataTransformer();
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'appendVariants',
                EntityIdentifierType::class,
                [
                    'class'    => $options['product_class'],
                    'required' => false,
                    'mapped'   => true,
                    'multiple' => true
                ]
            )
            ->add(
                'removeVariants',
                EntityIdentifierType::class,
                [
                    'class'    => $options['product_class'],
                    'required' => false,
                    'mapped'   => true,
                    'multiple' => true
                ]
            );

        $builder->addModelTransformer($this->transformer);
    }

    /**
     * @return string
     */
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
            'product_class' => null
        ]);
        $resolver->setRequired('product_class');
    }
}
