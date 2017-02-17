<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductVariantFieldType extends AbstractType
{
    const NAME = 'oro_product_variant_field';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('priority', 'hidden', ['empty_data' => 9999])
            ->add(
                'is_selected',
                'checkbox',
                [
                    'required' => false,
                    'attr' => ['data-original-name' => $builder->getName()],
                    'label' => $options['label']
                ]
            );
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
    public function getBlockPrefix()
    {
        return self::NAME;
    }
}
