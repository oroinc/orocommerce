<?php

namespace OroB2B\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

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
                ['required' => false, 'label' => 'orob2b.product.form.products.label']
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
    public function getName()
    {
        return self::NAME;
    }
}
