<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Processor;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Option as GatewayOption;

class PayPal implements Option\OptionsAwareInterface, ProcessorInterface
{
    const CODE = 'PayPal';
    const NAME = 'PayPal';

    /** {@inheritdoc} */
    public function configureOptions(Option\OptionsResolver $resolver)
    {
        if ($resolver->isDefined(GatewayOption\Swipe::SWIPE)) {
            $resolver->remove(GatewayOption\Swipe::SWIPE);
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
