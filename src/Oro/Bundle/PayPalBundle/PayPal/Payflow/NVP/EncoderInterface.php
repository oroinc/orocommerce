<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\NVP;

interface EncoderInterface
{
    /**
     * @param array $data
     * @return string
     */
    public function encode(array $data);

    /**
     * @param string $data
     * @return array
     */
    public function decode($data);
}
