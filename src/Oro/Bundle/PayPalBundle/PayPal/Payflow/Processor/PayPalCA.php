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

    #[\Override]
    public function getName()
    {
        return self::CODE;
    }

    #[\Override]
    public function getCode()
    {
        return self::NAME;
    }
}
