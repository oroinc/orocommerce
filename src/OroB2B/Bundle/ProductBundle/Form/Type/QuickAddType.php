<?php

namespace OroB2B\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuickAddType extends AbstractType
{
    const NAME = 'oro_product_quick_add';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'products',
                ProductRowCollectionType::NAME,
                [
                    'required' => false,
                    'label' => 'orob2b.product.form.products.label',
                    'options' => [
                        'validation_required' => $options['validation_required']
                    ]
                ]
            )
            ->add(
                'component',
                'hidden'
            )
            ->add(
                'additional',
                'hidden'
            );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'validation_required' => false
            ]
        );
        $resolver->setAllowedTypes('validation_required', 'bool');
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
