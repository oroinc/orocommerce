<?php

namespace OroB2B\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuickAddType extends AbstractType
{
    const NAME = 'orob2b_product_quick_add';

    const PRODUCTS_FIELD_NAME = 'products';
    const COMPONENT_FIELD_NAME = 'component';
    const ADDITIONAL_FIELD_NAME = 'additional';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                self::PRODUCTS_FIELD_NAME,
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
                self::COMPONENT_FIELD_NAME,
                'hidden'
            )
            ->add(
                self::ADDITIONAL_FIELD_NAME,
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
