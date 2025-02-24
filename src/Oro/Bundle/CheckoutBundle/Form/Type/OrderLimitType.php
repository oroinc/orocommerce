<?php

namespace Oro\Bundle\CheckoutBundle\Form\Type;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ValidationBundle\Validator\Constraints\GreaterThanZero;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Type;

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
                    'constraints' => [
                        new Type([
                            'type' => 'float',
                            'message' => 'oro.checkout.system_configuration.order_limits.message',
                        ]),
                        new GreaterThanZero([
                            'message' => 'oro.checkout.system_configuration.order_limits.message',
                        ]),
                    ],
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
