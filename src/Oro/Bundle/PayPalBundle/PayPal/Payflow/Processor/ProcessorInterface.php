<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Processor;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsAwareInterface;

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
