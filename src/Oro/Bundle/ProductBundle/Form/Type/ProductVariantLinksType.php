<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Oro\Bundle\ProductBundle\Form\DataTransformer\ProductVariantLinksDataTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductVariantLinksType extends AbstractType
{
    const NAME = 'oro_product_variant_links';

    /** @var ProductVariantLinksDataTransformer */
    protected $transformer;

    /**
     * @param ProductVariantLinksDataTransformer $transformer
     */
    public function __construct(ProductVariantLinksDataTransformer $transformer = null)
    {
        $this->transformer = $transformer ?: new ProductVariantLinksDataTransformer();
    }

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

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'product_class' => null
        ]);
        $resolver->setRequired('product_class');
    }
}
