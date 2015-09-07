<?php

namespace OroB2B\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use OroB2B\Bundle\ProductBundle\Validator\Constraints\ProductBySku;

class ProductRowType extends AbstractType
{
    const NAME = 'orob2b_product_row';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $productSkuOptions = [
            'required' => true,
            'label' => 'orob2b.product.sku.label'
        ];
        if ($options['validation_required']) {
            $productSkuOptions = array_merge(
                $productSkuOptions,
                [
                    'constraints' => [
                        new ProductBySku()
                    ]
                ]
            );
        }

        $builder
            ->add('productSku', 'text', $productSkuOptions)
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
