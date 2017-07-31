<?php

namespace Oro\Bundle\PromotionBundle\CouponGeneration\Generator;

use Oro\Bundle\PromotionBundle\CouponGeneration\Options\CodeGenerationOptions;

/**
 * This class is used for generating Coupon code by given options.
 */
class SimpleCodeGenerator implements CodeGeneratorInterface
{
    const DASHES_SYMBOL = '-';

    const NUMERIC_TEMPLATE = '123456789';

    const ALPHABETIC_TEMPLATE = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    const ALPHANUMERIC_TEMPLATE = '123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    /**
     * {@inheritdoc}
     */
    public function generate(CodeGenerationOptions $options)
    {
        $string = $this->generateRandomString($options->getCodeLength(), $options->getCodeType());
        $string = $options->getCodePrefix() . $string . $options->getCodeSuffix();
        if (!$options->getDashesSequence()) {
            return $string;
        }
        return implode(static::DASHES_SYMBOL, $this->splitString($string, $options->getDashesSequence()));
    }

    /**
     * @param int $length
     * @param string $type
     * @return string
     */
    protected function generateRandomString(int $length, string $type): string
    {
        if ($length === 0) {
            return '';
        }
        $template = $this->getTemplate($type);
        $max = mb_strlen($template) - 1;
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $template[random_int(0, $max)];
        }
        return $randomString;
    }

    /**
     * {@inheritdoc}
     */
    public function generateUnique(CodeGenerationOptions $options, $amount, array $excluded = [])
    {
        $codes = [];
        while (count($codes) < $amount) {
            do {
                $code = $this->generate($options);
            } while (array_key_exists($code, $excluded));
            $codes[(string)$code] = $code;
        }
        return $codes;
    }

    /**
     * @param string $type
     * @return string
     */
    protected function getTemplate(string $type): string
    {
        switch ($type) {
            case CodeGenerationOptions::NUMERIC_CODE_TYPE:
                return self::NUMERIC_TEMPLATE;
                break;
            case CodeGenerationOptions::ALPHABETIC_CODE_TYPE:
                return self::ALPHABETIC_TEMPLATE;
                break;
            default:
                $characters = self::ALPHANUMERIC_TEMPLATE;
        }
        return $characters;
    }

    /**
     * @param string $string
     * @param int $interval
     * @return array
     */
    protected function splitString(string $string, int $interval): array
    {
        $parts = [];
        $stringLength = mb_strlen($string, 'UTF-8');
        for ($i = 0; $i < $stringLength; $i += $interval) {
            $parts[] = mb_substr($string, $i, $interval);
        }
        return $parts;
    }
}
