<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Processor;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Option as GatewayOption;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

class PayPal implements Option\OptionsAwareInterface, ProcessorInterface
{
    public const CODE = 'PayPal';
    public const NAME = 'PayPal';

    #[\Override]
    public function configureOptions(Option\OptionsResolver $resolver)
    {
        if ($resolver->isDefined(GatewayOption\Swipe::SWIPE)) {
            $resolver->remove(GatewayOption\Swipe::SWIPE);
        }
    }

    #[\Override]
    public function getName()
    {
        return PayPal::CODE;
    }

    #[\Override]
    public function getCode()
    {
        return PayPal::NAME;
    }
}
