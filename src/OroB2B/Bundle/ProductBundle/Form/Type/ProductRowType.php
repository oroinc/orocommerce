<?php

namespace OroB2B\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class ProductRowType extends AbstractType
{
    const NAME = 'orob2b_product_row';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'productSku',
                'text',
                [
                    'required' => true,
                    'label' => 'orob2b.product.sku.label',
                ]
            )
            ->add(
                'productQuantity',
                'number',
                [
                    'required' => true,
                    'label' => 'orob2b.product.quantity.label',
                ]
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
