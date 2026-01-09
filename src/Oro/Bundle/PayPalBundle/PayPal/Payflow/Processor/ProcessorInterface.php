<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Processor;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsAwareInterface;

/**
 * Defines the contract for PayPal Payflow transaction processors.
 *
 * Handles processor-specific transaction configuration and identification.
 */
interface ProcessorInterface extends OptionsAwareInterface
{
    /**
     * @return string
     */
    public function getName();

    /**
     * @return string
     */
    public function getCode();
}
