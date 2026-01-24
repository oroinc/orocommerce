<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\NVP;

/**
 * Defines the contract for encoding and decoding PayPal Payflow NVP data.
 *
 * Handles serialization and deserialization of name-value pair data for PayPal API communication.
 */
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
