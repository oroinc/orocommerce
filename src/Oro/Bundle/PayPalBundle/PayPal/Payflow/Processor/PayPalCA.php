<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Processor;

/**
 * PayPalCA processor is an alias for PayPal processor.
 */
class PayPalCA extends PayPal
{
    /**
     * @internal
     */
    const CODE = 'PayPalCA';

    /**
     * @internal
     */
    const NAME = 'PayPalCA';

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return self::CODE;
    }

    /**
     * {@inheritDoc}
     */
    public function getCode()
    {
        return self::NAME;
    }
}
