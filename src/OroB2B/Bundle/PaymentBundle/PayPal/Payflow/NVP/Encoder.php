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
        $values = explode('&', $data);
        $result = [];

        foreach ($values as $value) {
            $keyValue = explode('=', $value);

            $key = $keyValue[0];

            if (false !== strpos($key, '[')) {
                $key = substr($key, 0, strpos($key, '['));
            }

            $result[$key] = $keyValue[1];
        }

        return $result;
    }
}
