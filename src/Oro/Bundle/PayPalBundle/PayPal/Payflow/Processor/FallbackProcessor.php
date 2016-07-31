<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Processor;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsResolver;

/**
 * Fallback for not implemented processors
 */
final class FallbackProcessor implements ProcessorInterface
{
    /** {@inheritdoc} */
    public function configureOptions(OptionsResolver $resolver)
    {
    }

    /** {@inheritdoc} */
    public function getName()
    {
        throw new \BadMethodCallException('Fallback processor should not be used directly');
    }

    /** {@inheritdoc} */
    public function getCode()
    {
        throw new \BadMethodCallException('Fallback processor should not be used directly');
    }
}
