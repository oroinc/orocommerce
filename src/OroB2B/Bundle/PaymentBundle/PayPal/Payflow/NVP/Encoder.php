<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\NVP;

class Encoder implements EncoderInterface
{
    /** {@inheritdoc} */
    public function encode(array $data)
    {
        $encodedData = [];
        foreach ($data as $key => $value) {
            $encodedData[] = sprintf('%s[%d]=%s', $key, strlen($value), $value);
        }

        return implode('&', $encodedData);
    }

    /** {@inheritdoc} */
    public function decode($data)
    {
        return explode('&', $data);
    }
}
