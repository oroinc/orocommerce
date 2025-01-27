<?php

namespace Oro\Bundle\CheckoutBundle\Form\Type;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Form type for minimum and maximum order amount settings
 */
class OrderLimitType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'value',
                NumberType::class,
                [
                    'scale' => Price::MAX_VALUE_SCALE,
                ]
            )
            ->add(
                'currency',
                HiddenType::class,
            );
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_checkout_order_limit';
    }
}
