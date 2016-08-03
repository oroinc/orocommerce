<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\NVP;

class Encoder implements EncoderInterface
{
    const DECODE_REGEXP = '/(\w+)(\[(\d+)\])?=/';

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
        $result = [];
        while (strlen($data) > 0) {
            $matches = [];
            preg_match(self::DECODE_REGEXP, $data, $matches);
            $key = $matches[1];
            $data = substr($data, strlen($matches[0]));
            if (isset($matches[3])) {
                $value = substr($data, 0, $matches[3]);
            } else {
                $next = strpos($data, '&');
                $value = $next === false ? $data : substr($data, 0, $next);
            }
            $data = substr($data, strlen($value) + 1);
            $result[$key] = $value === false ? '' : $value;
        }
        return $result;
    }
}
