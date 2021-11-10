<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The form type for line item on the storefront.
 */
class FrontendLineItemType extends AbstractType
{
    public const NAME = 'oro_product_frontend_line_item';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'unit',
                ProductUnitSelectionType::class,
                [
                    'required' => true,
                    'label' => 'oro.product.lineitem.unit.label',
                    'product_holder' => $builder->getData(),
                    'sell' => true,
                ]
            )
            ->add(
                'quantity',
                QuantityType::class,
                [
                    'required' => true,
                    'label' => 'oro.product.lineitem.quantity.enter',
                    'attr' => [
                        'placeholder' => 'oro.product.lineitem.quantity.placeholder',
                    ],
                    'grouping' => true,
                    'useInputTypeNumberValueFormat' => true
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'validation_groups' => ['add_product'],
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
