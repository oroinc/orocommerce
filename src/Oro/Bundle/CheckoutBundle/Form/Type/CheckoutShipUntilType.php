<?php

namespace Oro\Bundle\CheckoutBundle\Form\Type;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\FormBundle\Form\Type\OroDateType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for the checkout "ship until" date field.
 *
 * Provides a date picker form type for selecting the date by which an order must be shipped,
 * with validation to ensure the date is not in the past.
 */
class CheckoutShipUntilType extends AbstractType
{
    const NAME = 'oro_checkout_ship_until';

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }

    #[\Override]
    public function getParent(): ?string
    {
        return OroDateType::class;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'attr' => [
                'class' => 'datepicker-input input input--full',
                'placeholder' => 'oro.checkout.order_review.shipping_date_placeholder'
            ],
            'minDate' => '0',
        ]);

        $resolver->setRequired(['checkout']);
        $resolver->setAllowedValues('checkout', function ($value) {
            return $value instanceof Checkout;
        });
    }
}
