<?php

namespace OroB2B\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use OroB2B\Bundle\ProductBundle\Form\DataTransformer\ProductVariantLinksDataTransformer;

class ProductVariantLinksType extends AbstractType
{
    const NAME = 'orob2b_product_variant_links';

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

        $builder->addModelTransformer(new ProductVariantLinksDataTransformer());
    }

    /**
     * @return string
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'product_class' => null
        ]);
    }
}
