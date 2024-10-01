<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Processor;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsResolver;

/**
 * Fallback for not implemented processors
 */
final class FallbackProcessor implements ProcessorInterface
{
    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
    }

    #[\Override]
    public function getName()
    {
        throw new \BadMethodCallException('Fallback processor should not be used directly');
    }

    #[\Override]
    public function getCode()
    {
        throw new \BadMethodCallException('Fallback processor should not be used directly');
    }
}
