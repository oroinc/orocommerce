<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\ProductBundle\Form\DataTransformer\ProductVariantLinksDataTransformer;

class ProductVariantLinksType extends AbstractType
{
    const NAME = 'orob2b_product_variant_links';

    /** @var ProductVariantLinksDataTransformer */
    protected $transformer;

    /**
     * @param ProductVariantLinksDataTransformer $transformer
     */
    public function __construct(ProductVariantLinksDataTransformer $transformer = null)
    {
        $this->transformer = $transformer ?: new ProductVariantLinksDataTransformer();
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'appendVariants',
                'oro_entity_identifier',
                [
                    'class'    => $options['product_class'],
                    'required' => false,
                    'mapped'   => true,
                    'multiple' => true
                ]
            )
            ->add(
                'removeVariants',
                'oro_entity_identifier',
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

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'product_class' => null
        ]);
        $resolver->setRequired('product_class');
    }
}
