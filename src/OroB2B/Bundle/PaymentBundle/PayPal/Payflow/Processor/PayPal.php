<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Processor;

use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;

class PayPal implements Option\OptionsAwareInterface, ProcessorInterface
{
    const CODE = 'PayPal';
    const NAME = 'PayPal';

    /** {@inheritdoc} */
    public function configureOptions(Option\OptionsResolver $resolver)
    {
        if ($resolver->isDefined(Option\Swipe::SWIPE)) {
            $resolver->remove(Option\Swipe::SWIPE);
        }
    }

    /** {@inheritdoc} */
    public function getName()
    {
        return PayPal::CODE;
    }

    /** {@inheritdoc} */
    public function getCode()
    {
        return PayPal::NAME;
    }
}
