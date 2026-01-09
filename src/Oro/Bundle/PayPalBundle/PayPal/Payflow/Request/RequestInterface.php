<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Request;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsAwareInterface;

/**
 * Defines the contract for PayPal Payflow transaction requests.
 *
 * Handles request-specific transaction configuration and type identification.
 */
interface RequestInterface extends OptionsAwareInterface
{
    /**
     * @return string
     */
    public function getTransactionType();
}
