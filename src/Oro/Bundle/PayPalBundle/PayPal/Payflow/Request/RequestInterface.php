<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Request;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsAwareInterface;

interface RequestInterface extends OptionsAwareInterface
{
    /**
     * @return string
     */
    public function getTransactionType();
}
